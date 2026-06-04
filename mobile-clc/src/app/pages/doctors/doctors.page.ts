import { Component, inject } from '@angular/core';
import { ClinicService, Doctor, ServiceItem } from '../../services/clinic.service';

@Component({
  selector: 'app-doctors',
  templateUrl: './doctors.page.html',
  styleUrls: ['./doctors.page.scss'],
  standalone: false,
})
export class DoctorsPage {
  private readonly clinic = inject(ClinicService);
  doctors: Doctor[] = [];
  services: ServiceItem[] = [];

  ionViewWillEnter(): void {
    this.clinic.services().subscribe(data => this.services = data);
    this.clinic.doctors().subscribe(data => this.doctors = data);
  }

  doctorsForService(serviceId: number): Doctor[] {
    return this.doctors.filter(doctor => doctor.service?.id === serviceId);
  }
}
