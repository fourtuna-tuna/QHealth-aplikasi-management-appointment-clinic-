import { inject, Injectable } from '@angular/core';
import { BehaviorSubject, map, Observable, tap } from 'rxjs';
import { ApiService } from './api.service';

export interface Patient {
  id: number;
  name: string;
  email: string;
  role?: string;
  phone?: string;
  birth_date?: string;
  gender?: string;
  address?: string;
  blood_type?: string;
}

interface AuthPayload {
  user?: Patient;
  patient?: Patient;
  token?: string;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);
  private readonly storageKey = 'clc_user';
  private readonly tokenKey = 'clc_token';
  private readonly patientSubject = new BehaviorSubject<Patient | null>(this.readPatient());
  patient$ = this.patientSubject.asObservable();

  constructor() {
    localStorage.removeItem('clc_patient');
  }

  get patient(): Patient | null {
    return this.patientSubject.value;
  }

  get isAuthenticated(): boolean {
    return Boolean(localStorage.getItem(this.tokenKey) && this.patientSubject.value);
  }

  login(email: string, password: string): Observable<Patient> {
    return this.api.post<AuthPayload>('/auth/login', { email, password }).pipe(
      tap(payload => this.saveAuth(payload)),
      map(payload => this.authUser(payload)),
    );
  }

  register(data: Partial<Patient> & { password?: string; password_confirmation?: string }): Observable<Patient> {
    return this.api.post<AuthPayload>('/auth/register', data).pipe(
      map(payload => this.authUser(payload)),
    );
  }

  updateProfile(data: Partial<Patient>): Observable<Patient> {
    return this.api.patch<Patient>('/auth/profile', data).pipe(tap(patient => this.savePatient(patient)));
  }

  changePassword(data: { current_password: string; password: string; password_confirmation: string }): Observable<null> {
    return this.api.post<null>('/auth/change-password', data).pipe(tap(() => this.clearAuth()));
  }

  forgotPassword(email: string): Observable<null> {
    return this.api.post<null>('/auth/forgot-password', { email });
  }

  resetPassword(data: { email: string; token: string; password: string; password_confirmation: string }): Observable<null> {
    return this.api.post<null>('/auth/reset-password', data);
  }

  logout(): Observable<null> {
    return this.api.post<null>('/auth/logout', {}).pipe(tap(() => this.clearAuth()));
  }

  clearAuth(): void {
    localStorage.removeItem(this.tokenKey);
    localStorage.removeItem(this.storageKey);
    this.patientSubject.next(null);
  }

  private saveAuth(payload: AuthPayload): void {
    if (!payload.token) {
      throw new Error('Token login tidak ditemukan dari API.');
    }

    localStorage.setItem(this.tokenKey, payload.token);
    this.savePatient(this.authUser(payload));
  }

  private authUser(payload: AuthPayload): Patient {
    return (payload.user || payload.patient) as Patient;
  }

  private savePatient(patient: Patient): void {
    localStorage.setItem(this.storageKey, JSON.stringify(patient));
    this.patientSubject.next(patient);
  }

  private readPatient(): Patient | null {
    const raw = localStorage.getItem(this.storageKey);
    if (!raw) {
      return null;
    }

    try {
      return JSON.parse(raw);
    } catch (error) {
      console.warn('[CLC Auth] Data pasien di localStorage rusak, logout otomatis.', error);
      localStorage.removeItem(this.storageKey);
      return null;
    }
  }
}
