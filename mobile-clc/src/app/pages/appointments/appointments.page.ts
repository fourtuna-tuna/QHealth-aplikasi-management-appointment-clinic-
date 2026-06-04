import { Component, inject } from '@angular/core';
import { ToastController } from '@ionic/angular';
import { Appointment, ClinicService, Doctor, Schedule } from '../../services/clinic.service';

@Component({
  selector: 'app-appointments',
  templateUrl: './appointments.page.html',
  styleUrls: ['./appointments.page.scss'],
  standalone: false,
})
export class AppointmentsPage {
  private readonly clinic = inject(ClinicService);
  private readonly toast = inject(ToastController);
  doctors: Doctor[] = [];
  schedules: Schedule[] = [];
  appointments: Appointment[] = [];
  activeAppointments: Appointment[] = [];
  form = { doctor_id: 0, doctor_schedule_id: 0, appointment_date: this.tomorrow(), complaint: '' };
  private readonly activeStatuses = ['booked'];

  ionViewWillEnter(): void {
    this.refresh();
  }

  refresh(): void {
    this.clinic.doctors().subscribe(data => {
      this.doctors = data;
      if (!this.form.doctor_id && data[0]) {
        this.form.doctor_id = (data.find(doctor => doctor.schedules?.length) || data[0]).id;
        this.loadSchedules();
      }
    });
    this.clinic.appointments().subscribe(data => {
      this.appointments = data;
      this.activeAppointments = data.filter(appointment => this.activeStatuses.includes(appointment.status));
    });
  }

  loadSchedules(): void {
    this.clinic.schedules(Number(this.form.doctor_id)).subscribe(data => {
      this.schedules = data;
      this.form.doctor_schedule_id = data[0]?.id || 0;
    });
  }

  submit(): void {
    if (!this.form.doctor_id || !this.form.doctor_schedule_id || !this.form.appointment_date || !this.form.complaint.trim()) {
      this.toast.create({ message: 'Lengkapi dokter, jadwal, tanggal, dan keluhan terlebih dahulu', duration: 2200, color: 'warning' })
        .then(toast => toast.present());
      return;
    }

    this.clinic.createAppointment(this.form).subscribe({
      next: async () => {
        this.form.complaint = '';
        this.refresh();
        (await this.toast.create({ message: 'Booking berhasil dibuat', duration: 1600, color: 'primary' })).present();
      },
      error: async err => {
        (await this.toast.create({ message: err.userMessage || err.message || 'Booking gagal', duration: 2600, color: 'danger' })).present();
      },
    });
  }

  checkIn(appointment: Appointment): void {
    this.clinic.checkIn(appointment.id).subscribe(() => this.refresh());
  }

  cancel(appointment: Appointment): void {
    this.clinic.cancelAppointment(appointment.id).subscribe(() => this.refresh());
  }

  private tomorrow(): string {
    const date = new Date();
    date.setDate(date.getDate() + 1);
    return date.toISOString().slice(0, 10);
  }
}
