import { Component, inject } from '@angular/core';
import { ToastController } from '@ionic/angular';
import { finalize } from 'rxjs';
import { AuthService } from '../../services/auth.service';
import { Appointment, ClinicService, Doctor, Schedule } from '../../services/clinic.service';
import { OfflineSyncService } from '../../services/offline-sync.service';

@Component({
  selector: 'app-appointments',
  templateUrl: './appointments.page.html',
  styleUrls: ['./appointments.page.scss'],
  standalone: false,
})
export class AppointmentsPage {
  private readonly auth = inject(AuthService);
  private readonly clinic = inject(ClinicService);
  private readonly offlineSync = inject(OfflineSyncService);
  private readonly toast = inject(ToastController);
  doctors: Doctor[] = [];
  schedules: Schedule[] = [];
  appointments: Appointment[] = [];
  activeAppointments: Appointment[] = [];
  form = { doctor_id: 0, doctor_schedule_id: 0, appointment_date: this.tomorrow(), complaint: '' };
  private readonly activeStatuses = ['booked', 'pending', 'checked_in'];
  loading = false;
  isSubmitting = false;

  ionViewWillEnter(): void {
    void this.offlineSync.syncPendingRequests();
    this.refresh();
  }

  refresh(): void {
    this.loading = true;
    this.appointments = [];
    this.activeAppointments = [];

    this.clinic.doctors().subscribe(data => {
      this.doctors = data;
      if (!this.form.doctor_id && data[0]) {
        this.form.doctor_id = (data.find(doctor => doctor.schedules?.length) || data[0]).id;
        this.loadSchedules();
      }
    });
    this.clinic.appointments().subscribe({
      next: data => {
        this.appointments = data;
        this.activeAppointments = this.appointments.filter(appointment => this.activeStatuses.includes(appointment.status));
        this.loading = false;
      },
      error: () => {
        this.appointments = [];
        this.activeAppointments = [];
        this.loading = false;
      },
    });
  }

  loadSchedules(): void {
    this.clinic.schedules(Number(this.form.doctor_id)).subscribe(data => {
      this.schedules = data;
      this.form.doctor_schedule_id = data[0]?.id || 0;
    });
  }

  submit(): void {
    if (this.isSubmitting) {
      return;
    }

    if (!this.form.doctor_id || !this.form.doctor_schedule_id || !this.form.appointment_date || !this.form.complaint.trim()) {
      this.toast.create({ message: 'Lengkapi dokter, jadwal, tanggal, dan keluhan terlebih dahulu', duration: 2200, color: 'warning' })
        .then(toast => toast.present());
      return;
    }

    this.isSubmitting = true;
    const body = {
      ...this.form,
      client_user_id: this.auth.patient?.id,
      client_request_id: `booking-${Date.now()}-${Math.random().toString(16).slice(2)}`,
    };

    if (!this.offlineSync.isOnline()) {
      this.offlineSync.addPendingRequest({ method: 'POST', url: '/patient/appointments', body });
      this.isSubmitting = false;
      return;
    }

    this.clinic.createAppointment(body).pipe(finalize(() => this.isSubmitting = false)).subscribe({
      next: async () => {
        this.form.complaint = '';
        this.refresh();
        (await this.toast.create({ message: 'Booking berhasil dibuat', duration: 1600, color: 'primary' })).present();
      },
      error: async err => {
        if (err.status === 0) {
          this.offlineSync.addPendingRequest({ method: 'POST', url: '/patient/appointments', body });
          return;
        }

        (await this.toast.create({ message: err.userMessage || err.message || 'Booking gagal', duration: 2600, color: 'danger' })).present();
      },
    });
  }

  private tomorrow(): string {
    const date = new Date();
    date.setDate(date.getDate() + 1);
    return date.toISOString().slice(0, 10);
  }

  statusLabel(status: string): string {
    const labels: Record<string, string> = {
      pending: 'Menunggu konfirmasi',
      booked: 'Booking berhasil',
      checked_in: 'Sudah check-in, silakan tunggu dipanggil.',
      completed: 'Selesai',
      cancelled: 'Dibatalkan karena melewati waktu kedatangan',
    };

    return labels[status] || status || '-';
  }
}
