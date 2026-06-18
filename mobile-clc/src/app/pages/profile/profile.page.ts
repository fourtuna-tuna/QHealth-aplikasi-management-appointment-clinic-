import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { ToastController } from '@ionic/angular';
import { AuthService, Patient } from '../../services/auth.service';
import { ClinicService, QueueInfo } from '../../services/clinic.service';
import { OfflineSyncService } from '../../services/offline-sync.service';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.page.html',
  styleUrls: ['./profile.page.scss'],
  standalone: false,
})
export class ProfilePage {
  private readonly auth = inject(AuthService);
  private readonly clinic = inject(ClinicService);
  private readonly offlineSync = inject(OfflineSyncService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastController);
  form: Partial<Patient> = {};
  passwordForm = { current_password: '', password: '', password_confirmation: '' };
  queue: QueueInfo | null = null;

  ionViewWillEnter(): void {
    void this.offlineSync.syncPendingRequests();
    this.form = { ...(this.auth.patient || {}), ...this.latestPendingProfileBody() };
    this.queue = null;
    this.clinic.queue().subscribe({
      next: data => this.queue = data,
      error: () => this.queue = null,
    });
  }

  save(): void {
    if (!this.offlineSync.isOnline()) {
      this.offlineSync.addPendingRequest({ method: 'PATCH', url: '/auth/profile', body: this.form });
      return;
    }

    this.auth.updateProfile(this.form).subscribe({
      next: async patient => {
        this.form = { ...patient };
        (await this.toast.create({ message: 'Profil berhasil diperbarui', duration: 1400, color: 'primary' })).present();
      },
      error: async err => {
        if (err.status === 0) {
          this.offlineSync.addPendingRequest({ method: 'PATCH', url: '/auth/profile', body: this.form });
          return;
        }

        (await this.toast.create({ message: err.userMessage || err.message || 'Profil gagal diperbarui', duration: 2600, color: 'danger' })).present();
      },
    });
  }

  sendResetPassword(): void {
    if (!this.form.email) {
      return;
    }

    this.auth.forgotPassword(this.form.email).subscribe(async () => {
      (await this.toast.create({ message: 'Link reset password dikirim ke email Anda', duration: 2200, color: 'primary' })).present();
    });
  }

  changePassword(): void {
    this.auth.changePassword(this.passwordForm).subscribe({
      next: async () => {
        this.passwordForm = { current_password: '', password: '', password_confirmation: '' };
        (await this.toast.create({ message: 'Password berhasil diganti. Silakan login kembali.', duration: 2200, color: 'primary' })).present();
        this.router.navigateByUrl('/auth', { replaceUrl: true });
      },
      error: async err => {
        (await this.toast.create({ message: err.userMessage || err.message || 'Ganti password gagal', duration: 2600, color: 'danger' })).present();
      },
    });
  }

  logout(): void {
    this.auth.logout().subscribe({
      next: () => this.router.navigateByUrl('/auth', { replaceUrl: true }),
      error: () => {
        this.auth.clearAuth();
        this.router.navigateByUrl('/auth', { replaceUrl: true });
      },
    });
  }

  private latestPendingProfileBody(): Partial<Patient> {
    const pendingProfile = [...this.offlineSync.getPendingRequests()]
      .reverse()
      .find(request => request.url === '/auth/profile' && request.method === 'PATCH');

    return pendingProfile?.body && typeof pendingProfile.body === 'object'
      ? pendingProfile.body as Partial<Patient>
      : {};
  }
}
