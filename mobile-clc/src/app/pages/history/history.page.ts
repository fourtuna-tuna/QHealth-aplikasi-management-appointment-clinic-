import { Component, inject } from '@angular/core';
import { ClinicService, MedicalRecord } from '../../services/clinic.service';

@Component({
  selector: 'app-history',
  templateUrl: './history.page.html',
  styleUrls: ['./history.page.scss'],
  standalone: false,
})
export class HistoryPage {
  private readonly clinic = inject(ClinicService);
  records: MedicalRecord[] = [];

  ionViewWillEnter(): void {
    this.clinic.histories().subscribe(data => this.records = data);
  }
}
