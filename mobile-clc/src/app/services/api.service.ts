import { HttpClient, HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, Observable, throwError } from 'rxjs';
import { environment } from '../../environments/environment';

interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
}

export interface ApiClientError {
  status: number;
  message: string;
  userMessage: string;
  url?: string | null;
  original: HttpErrorResponse;
}

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = this.resolveBaseUrl();

  constructor() {
    console.info('[QHealth API] Base URL:', this.baseUrl);
  }

  get<T>(path: string): Observable<T> {
    return this.http.get<ApiResponse<T>>(`${this.baseUrl}${path}`, { headers: this.headers() }).pipe(
      map(res => res.data),
      catchError(error => this.handleError(error, path)),
    );
  }

  post<T>(path: string, body: unknown): Observable<T> {
    return this.http.post<ApiResponse<T>>(`${this.baseUrl}${path}`, body, { headers: this.headers() }).pipe(
      map(res => res.data),
      catchError(error => this.handleError(error, path)),
    );
  }

  patch<T>(path: string, body: unknown): Observable<T> {
    return this.http.patch<ApiResponse<T>>(`${this.baseUrl}${path}`, body, { headers: this.headers() }).pipe(
      map(res => res.data),
      catchError(error => this.handleError(error, path)),
    );
  }

  delete<T>(path: string): Observable<T> {
    return this.http.delete<ApiResponse<T>>(`${this.baseUrl}${path}`, { headers: this.headers() }).pipe(
      map(res => res.data),
      catchError(error => this.handleError(error, path)),
    );
  }

  private headers(): HttpHeaders {
    const token = localStorage.getItem('clc_token');

    return token ? new HttpHeaders({ Authorization: `Bearer ${token}` }) : new HttpHeaders();
  }

  private resolveBaseUrl(): string {
    const override = localStorage.getItem('clc_api_url')?.trim();
    const configuredUrl = override || environment.apiUrl;

    if (typeof window === 'undefined') {
      return configuredUrl;
    }

    const frontendHost = window.location.hostname;
    const isFrontendLocalhost = ['localhost', '127.0.0.1', '::1'].includes(frontendHost);

    try {
      const url = new URL(configuredUrl);
      const isApiLocalhost = ['localhost', '127.0.0.1', '::1'].includes(url.hostname);

      if (isApiLocalhost && !isFrontendLocalhost && window.location.protocol.startsWith('http')) {
        url.hostname = frontendHost;
        return url.toString().replace(/\/$/, '');
      }
    } catch {
      console.warn('[QHealth API] apiUrl tidak valid:', configuredUrl);
    }

    return configuredUrl.replace(/\/$/, '');
  }

  private handleError(error: HttpErrorResponse, path: string): Observable<never> {
    if (error.status === 401) {
      localStorage.removeItem('clc_token');
      localStorage.removeItem('clc_user');
      localStorage.removeItem('clc_patient');
    }

    const apiError: ApiClientError = {
      status: error.status,
      message: error.error?.message || error.message || 'API request failed',
      userMessage: this.userMessage(error),
      url: error.url || `${this.baseUrl}${path}`,
      original: error,
    };

    console.error('[QHealth API] Request gagal', {
      methodPath: path,
      url: apiError.url,
      status: apiError.status,
      message: apiError.message,
      response: error.error,
    });

    return throwError(() => apiError);
  }

  private userMessage(error: HttpErrorResponse): string {
    if (error.status === 0) {
      return `Tidak bisa terhubung ke API Laravel (${this.baseUrl}). Pastikan Laravel berjalan dan URL dapat diakses dari browser/perangkat.`;
    }

    return error.error?.message || `API Laravel error (${error.status})`;
  }
}
