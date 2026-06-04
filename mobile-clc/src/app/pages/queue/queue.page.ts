import { Component, inject } from '@angular/core';
import { ToastController } from '@ionic/angular';
import { ClinicService, QueueInfo } from '../../services/clinic.service';

@Component({
  selector: 'app-queue',
  templateUrl: './queue.page.html',
  styleUrls: ['./queue.page.scss'],
  standalone: false,
})
export class QueuePage {
  private readonly clinic = inject(ClinicService);
  private readonly toast = inject(ToastController);
  queue: QueueInfo | null = null;
  private refreshTimer?: ReturnType<typeof setInterval>;

  ionViewWillEnter(): void {
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
    this.clinic.queue().subscribe(data => this.queue = data);
  }

  checkIn(): void {
    const appointment = this.queue?.appointment;

    if (!appointment) {
      return;
    }

    this.clinic.checkIn(appointment.id).subscribe(async () => {
      this.queue = null;
      (await this.toast.create({ message: 'Check-in berhasil. Riwayat kunjungan sudah dibuat.', duration: 2200, color: 'primary' })).present();
    });
  }

  cancel(): void {
    const appointment = this.queue?.appointment;

    if (!appointment) {
      return;
    }

    this.clinic.cancelAppointment(appointment.id).subscribe(async () => {
      this.queue = null;
      (await this.toast.create({ message: 'Booking berhasil dibatalkan', duration: 1600, color: 'medium' })).present();
    });
  }
}
