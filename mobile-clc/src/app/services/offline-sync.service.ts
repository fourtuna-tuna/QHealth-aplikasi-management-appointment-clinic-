import { inject, Injectable, OnDestroy } from '@angular/core';
import { ToastController } from '@ionic/angular';
import { BehaviorSubject, firstValueFrom } from 'rxjs';
import { ApiClientError, ApiService } from './api.service';

export type OfflineRequestMethod = 'POST' | 'PATCH' | 'DELETE';
export type OfflineRequestStatus = 'pending' | 'failed';

export interface OfflineRequest {
  id: string;
  method: OfflineRequestMethod;
  url: string;
  body?: unknown;
  createdAt: string;
  status: OfflineRequestStatus;
  errorMessage?: string;
}

type NewOfflineRequest = Pick<OfflineRequest, 'method' | 'url' | 'body'>;

@Injectable({ providedIn: 'root' })
export class OfflineSyncService implements OnDestroy {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastController);
  private readonly storageKey = 'clc_offline_queue';
  private readonly tokenKey = 'clc_token';
  private readonly onlineSubject = new BehaviorSubject<boolean>(this.isOnline());
  private readonly pendingCountSubject = new BehaviorSubject<number>(this.getPendingRequests().length);
  private syncing = false;
  readonly online$ = this.onlineSubject.asObservable();
  readonly pendingCount$ = this.pendingCountSubject.asObservable();

  private readonly onlineHandler = () => {
    this.onlineSubject.next(true);
    void this.syncPendingRequests();
  };
  private readonly offlineHandler = () => {
    this.onlineSubject.next(false);
    void this.presentToast('Anda sedang offline', 'warning');
  };

  constructor() {
    window.addEventListener('online', this.onlineHandler);
    window.addEventListener('offline', this.offlineHandler);
  }

  ngOnDestroy(): void {
    window.removeEventListener('online', this.onlineHandler);
    window.removeEventListener('offline', this.offlineHandler);
  }

  isOnline(): boolean {
    return window.navigator.onLine;
  }

  addPendingRequest(request: NewOfflineRequest): OfflineRequest {
    const queue = this.getPendingRequests();
    const duplicateRequest = this.findDuplicateRequest(queue, request);

    if (duplicateRequest) {
      void this.presentToast('Data ini sudah tersimpan dan menunggu sinkronisasi.', 'warning', 2600);
      return duplicateRequest;
    }

    const pendingRequest: OfflineRequest = {
      ...request,
      id: this.uniqueId(),
      createdAt: new Date().toISOString(),
      status: 'pending',
    };
    queue.push(pendingRequest);
    this.saveQueue(queue);
    void this.presentToast('Anda sedang offline. Data disimpan sementara dan akan dikirim saat online.', 'warning', 3200);

    return pendingRequest;
  }

  getPendingRequests(): OfflineRequest[] {
    const raw = localStorage.getItem(this.storageKey);
    if (!raw) {
      return [];
    }

    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      console.warn('[QHealth Offline] Queue lokal rusak, queue direset.', error);
      localStorage.removeItem(this.storageKey);
      return [];
    }
  }

  async syncPendingRequests(): Promise<void> {
    if (this.syncing || !this.isOnline()) {
      this.refreshState();
      return;
    }

    let queue = this.getPendingRequests();
    const syncableQueue = queue.filter(request => request.status === 'pending');
    if (syncableQueue.length === 0) {
      this.refreshState();
      return;
    }

    if (!localStorage.getItem(this.tokenKey) && queue.some(request => this.isProtectedRequest(request.url))) {
      await this.presentToast('Ada data offline yang belum terkirim. Silakan login ulang untuk sinkronisasi.', 'warning', 3200);
      this.refreshState();
      return;
    }

    this.syncing = true;
    let syncedCount = 0;

    for (const request of syncableQueue) {
      if (!this.isOnline()) {
        break;
      }

      try {
        await this.sendRequest(request);
        queue = queue.filter(item => item.id !== request.id);
        syncedCount += 1;
        this.saveQueue(queue);
      } catch (error) {
        const apiError = error as ApiClientError;
        queue = this.getPendingRequests();

        if (apiError?.status === 0 || !this.isOnline()) {
          break;
        }

        const errorMessage = this.isActiveQueueConflict(request, apiError)
          ? 'Anda masih memiliki antrean aktif.'
          : apiError?.userMessage || apiError?.message || 'Sinkronisasi gagal';

        queue = queue.map(item => item.id === request.id
          ? { ...item, status: 'failed', errorMessage }
          : item);
        this.saveQueue(queue);
        await this.presentToast(errorMessage || 'Data offline gagal disinkronkan.', 'danger', 3200);
      }
    }

    this.syncing = false;
    this.refreshState();

    if (syncedCount > 0) {
      await this.presentToast('Data offline berhasil disinkronkan.', 'primary', 2200);
    }
  }

  clearSyncedRequest(id: string): void {
    this.saveQueue(this.getPendingRequests().filter(request => request.id !== id));
  }

  private sendRequest(request: OfflineRequest): Promise<unknown> {
    switch (request.method) {
      case 'POST':
        return firstValueFrom(this.api.post(request.url, request.body || {}));
      case 'PATCH':
        return firstValueFrom(this.api.patch(request.url, request.body || {}));
      case 'DELETE':
        return firstValueFrom(this.api.delete(request.url));
    }
  }

  private saveQueue(queue: OfflineRequest[]): void {
    localStorage.setItem(this.storageKey, JSON.stringify(queue));
    this.pendingCountSubject.next(queue.length);
  }

  private findDuplicateRequest(queue: OfflineRequest[], request: NewOfflineRequest): OfflineRequest | undefined {
    return queue.find(item => item.status === 'pending'
      && item.method === request.method
      && item.url === request.url
      && this.isSameRequestBody(item.body, request.body, request.url));
  }

  private isSameRequestBody(left: unknown, right: unknown, url: string): boolean {
    if (url === '/patient/appointments') {
      return this.isSameBookingBody(left, right);
    }

    return JSON.stringify(this.normalizeBody(left)) === JSON.stringify(this.normalizeBody(right));
  }

  private isSameBookingBody(left: unknown, right: unknown): boolean {
    const leftBody = this.normalizeBody(left);
    const rightBody = this.normalizeBody(right);
    const leftUserId = leftBody['client_user_id'];
    const rightUserId = rightBody['client_user_id'];

    if (leftUserId && rightUserId && String(leftUserId) !== String(rightUserId)) {
      return false;
    }

    return String(leftBody['doctor_id'] || '') === String(rightBody['doctor_id'] || '')
      && String(leftBody['doctor_schedule_id'] || '') === String(rightBody['doctor_schedule_id'] || '')
      && String(leftBody['appointment_date'] || '') === String(rightBody['appointment_date'] || '')
      && String(leftBody['complaint'] || '').trim() === String(rightBody['complaint'] || '').trim();
  }

  private normalizeBody(body: unknown): Record<string, unknown> {
    if (!body || typeof body !== 'object' || Array.isArray(body)) {
      return {};
    }

    const normalized = { ...(body as Record<string, unknown>) };
    delete normalized['client_request_id'];

    return normalized;
  }

  private refreshState(): void {
    this.onlineSubject.next(this.isOnline());
    this.pendingCountSubject.next(this.getPendingRequests().length);
  }

  private uniqueId(): string {
    const random = typeof crypto !== 'undefined' && 'randomUUID' in crypto
      ? crypto.randomUUID()
      : `${Date.now()}-${Math.random().toString(16).slice(2)}`;

    return `offline-${random}`;
  }

  private isProtectedRequest(url: string): boolean {
    return url.startsWith('/patient/')
      || ['/auth/me', '/auth/profile', '/auth/change-password', '/auth/logout'].includes(url);
  }

  private isActiveQueueConflict(request: OfflineRequest, error: ApiClientError): boolean {
    const message = `${error?.userMessage || ''} ${error?.message || ''}`.toLowerCase();

    return request.method === 'POST'
      && request.url === '/patient/appointments'
      && [409, 422].includes(error?.status)
      && message.includes('antrean aktif');
  }

  private async presentToast(message: string, color: string, duration = 1800): Promise<void> {
    const toast = await this.toast.create({ message, duration, color });
    await toast.present();
  }
}
