import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';

export interface ServiceItem {
  id: number;
  name: string;
  code: string;
  description: string;
  price: number;
  duration_minutes: number;
  doctors_count?: number;
}

export interface Doctor {
  id: number;
  name: string;
  specialization: string;
  sip_number?: string;
  bio?: string;
  service?: ServiceItem;
  schedules?: Schedule[];
}

export interface Schedule {
  id: number;
  doctor_id: number;
  day: string;
  start_time: string;
  end_time: string;
  quota: number;
}

export interface Appointment {
  id: number;
  user_id?: number | string;
  patient_id?: number | string;
  doctor_id: number;
  doctor?: Doctor;
  schedule?: Schedule;
  appointment_date: string;
  queue_number: number;
  complaint: string;
  status: string;
  payment_status: 'unpaid' | 'paid';
  paid_at?: string;
}

export interface QueueInfo {
  appointment: Appointment;
  current_queue: number;
  remaining_queue: number;
  my_queue_number?: number;
  current_queue_number?: number;
  service_name?: string;
  active_appointment_status?: string;
}

export interface MedicalRecord {
  id: number;
  doctor?: Doctor;
  appointment?: Appointment;
  diagnosis: string;
  prescription?: string;
  treatment?: string;
  doctor_notes?: string;
  visited_at: string;
}

@Injectable({ providedIn: 'root' })
export class ClinicService {
  private readonly api = inject(ApiService);

  services(): Observable<ServiceItem[]> { return this.api.get('/services'); }
  doctors(serviceId?: number): Observable<Doctor[]> { return this.api.get(`/doctors${serviceId ? `?service_id=${serviceId}` : ''}`); }
  schedules(doctorId: number): Observable<Schedule[]> { return this.api.get(`/doctors/${doctorId}/schedules`); }
  appointments(): Observable<Appointment[]> { return this.api.get('/patient/appointments'); }
  createAppointment(data: unknown): Observable<Appointment> { return this.api.post('/patient/appointments', data); }
  queue(): Observable<QueueInfo | null> { return this.api.get('/patient/queue'); }
  histories(): Observable<MedicalRecord[]> { return this.api.get('/patient/histories'); }
}
