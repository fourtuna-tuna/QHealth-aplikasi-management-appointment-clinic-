import { Component, inject } from '@angular/core';
import { ClinicService, QueueInfo } from '../../services/clinic.service';
import { OfflineSyncService } from '../../services/offline-sync.service';

@Component({
  selector: 'app-queue',
  templateUrl: './queue.page.html',
  styleUrls: ['./queue.page.scss'],
  standalone: false,
})
export class QueuePage {
  private readonly clinic = inject(ClinicService);
  private readonly offlineSync = inject(OfflineSyncService);
  queue: QueueInfo | null = null;
  loading = false;
  private refreshTimer?: ReturnType<typeof setInterval>;

  ionViewWillEnter(): void {
    void this.offlineSync.syncPendingRequests();
    this.loadQueue();
    this.refreshTimer = setInterval(() => this.loadQueue(), 10000);
  }

  ionViewDidLeave(): void {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
      this.refreshTimer = undefined;
    }
  }

  private loadQueue(): void {
    this.loading = true;
    this.queue = null;
    this.clinic.queue().subscribe({
      next: data => {
        this.queue = data;
        this.loading = false;
      },
      error: () => {
        this.queue = null;
        this.loading = false;
      },
    });
  }

  statusLabel(status: string): string {
    const labels: Record<string, string> = {
      pending: 'Menunggu konfirmasi',
      booked: 'Booking berhasil, silakan datang sesuai jadwal.',
      checked_in: 'Sudah check-in, silakan tunggu dipanggil.',
      in_queue: 'Sedang dalam antrean',
      in_progress: 'Sedang dilayani',
      completed: 'Selesai',
      cancelled: 'Dibatalkan karena melewati waktu kedatangan',
      reset: 'Direset',
    };

    return labels[status] || status || '-';
  }

}
