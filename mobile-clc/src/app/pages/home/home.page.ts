import { Component, inject, OnInit } from '@angular/core';
import { AuthService, Patient } from '../../services/auth.service';
import { Appointment, ClinicService, QueueInfo, ServiceItem } from '../../services/clinic.service';
import { OfflineSyncService } from '../../services/offline-sync.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.page.html',
  styleUrls: ['./home.page.scss'],
  standalone: false,
})
export class HomePage implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly clinic = inject(ClinicService);
  private readonly offlineSync = inject(OfflineSyncService);
  patient: Patient | null = null;
  services: ServiceItem[] = [];
  appointments: Appointment[] = [];
  activeAppointments: Appointment[] = [];
  queue: QueueInfo | null = null;
  private readonly activeStatuses = ['booked', 'pending', 'checked_in', 'in_queue', 'in_progress'];

  ngOnInit(): void {
    this.patient = this.auth.patient;
    this.refresh();
  }

  ionViewWillEnter(): void {
    void this.offlineSync.syncPendingRequests();
    this.patient = this.auth.patient;
    this.refresh();
  }

  refresh(): void {
    this.appointments = [];
    this.activeAppointments = [];
    this.queue = null;

    this.clinic.services().subscribe(data => this.services = data);
    this.clinic.appointments().subscribe({
      next: data => {
        this.appointments = data;
        this.activeAppointments = this.appointments.filter(appointment => this.activeStatuses.includes(appointment.status));
      },
      error: () => {
        this.appointments = [];
        this.activeAppointments = [];
      },
    });
    this.clinic.queue().subscribe({
      next: data => this.queue = data,
      error: () => this.queue = null,
    });
  }

  queueNumber(value?: number | null): string {
    return value ? `A${String(value).padStart(3, '0')}` : '-';
  }

  currentQueueNumber(): string {
    return this.queueNumber(this.queue?.current_queue_number ?? null);
  }

  myQueueNumber(): string {
    return this.queueNumber(this.queue?.my_queue_number ?? this.queue?.appointment?.queue_number ?? null);
  }

  queueNote(): string {
    return this.queue
      ? (this.queue.service_name || this.queue.appointment?.doctor?.service?.name || 'Antrean aktif Anda')
      : 'Belum ada antrean aktif';
  }
}
