import { Component, inject } from '@angular/core';
import { ClinicService, MedicalRecord } from '../../services/clinic.service';
import { OfflineSyncService } from '../../services/offline-sync.service';

@Component({
  selector: 'app-history',
  templateUrl: './history.page.html',
  styleUrls: ['./history.page.scss'],
  standalone: false,
})
export class HistoryPage {
  private readonly clinic = inject(ClinicService);
  private readonly offlineSync = inject(OfflineSyncService);
  records: MedicalRecord[] = [];
  loading = false;

  ionViewWillEnter(): void {
    void this.offlineSync.syncPendingRequests();
    this.loading = true;
    this.records = [];
    this.clinic.histories().subscribe({
      next: data => {
        this.records = data;
        this.loading = false;
      },
      error: () => {
        this.records = [];
        this.loading = false;
      },
    });
  }
}
