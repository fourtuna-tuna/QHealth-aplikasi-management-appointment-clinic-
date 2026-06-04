import { Component, inject, OnInit } from '@angular/core';
import { AuthService, Patient } from '../../services/auth.service';
import { Appointment, ClinicService, QueueInfo, ServiceItem } from '../../services/clinic.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.page.html',
  styleUrls: ['./home.page.scss'],
  standalone: false,
})
export class HomePage implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly clinic = inject(ClinicService);
  patient: Patient | null = null;
  services: ServiceItem[] = [];
  appointments: Appointment[] = [];
  activeAppointments: Appointment[] = [];
  queue: QueueInfo | null = null;
  private readonly activeStatuses = ['booked'];

  ngOnInit(): void {
    this.patient = this.auth.patient;
    this.refresh();
  }

  ionViewWillEnter(): void {
    this.refresh();
  }

  refresh(): void {
    this.clinic.services().subscribe(data => this.services = data);
    this.clinic.appointments().subscribe(data => {
      this.appointments = data;
      this.activeAppointments = data.filter(appointment => this.activeStatuses.includes(appointment.status));
    });
    this.clinic.queue().subscribe(data => this.queue = data);
  }
}
