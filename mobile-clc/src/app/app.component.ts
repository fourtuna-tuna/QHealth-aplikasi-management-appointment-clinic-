import { Location } from '@angular/common';
import { Component, inject, OnDestroy } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { App } from '@capacitor/app';
import { AlertController, Platform, ToastController } from '@ionic/angular';
import { addIcons } from 'ionicons';
import { calendarOutline, checkmarkCircleOutline, documentTextOutline, homeOutline, medkitOutline, personOutline, ticketOutline } from 'ionicons/icons';
import { filter, Subscription } from 'rxjs';
import { OfflineSyncService } from './services/offline-sync.service';
import { PrivacyPolicyService } from './services/privacy-policy.service';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss'],
  standalone: false,
})
export class AppComponent implements OnDestroy {
  private readonly router = inject(Router);
  private readonly location = inject(Location);
  private readonly platform = inject(Platform);
  private readonly toast = inject(ToastController);
  private readonly alert = inject(AlertController);
  private readonly privacyPolicy = inject(PrivacyPolicyService);
  private readonly offlineSync = inject(OfflineSyncService);
  readonly isOnline$ = this.offlineSync.online$;
  readonly pendingCount$ = this.offlineSync.pendingCount$;
  private readonly invalidSessionHandler = (event: Event) => this.handleInvalidSession(event);
  private privacyAlertOpen = false;
  private readonly homeUrl = '/tabs/home';
  private readonly navigationHistory: string[] = [];
  private readonly routerEventsSubscription: Subscription;
  private readonly backButtonSubscription: Subscription;

  constructor() {
    addIcons({ homeOutline, medkitOutline, calendarOutline, ticketOutline, documentTextOutline, personOutline, checkmarkCircleOutline });
    window.addEventListener('clc-auth-invalid', this.invalidSessionHandler);
    this.routerEventsSubscription = this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe(event => this.trackNavigation(event.urlAfterRedirects));
    this.backButtonSubscription = this.platform.backButton.subscribeWithPriority(10, processNextHandler => this.handleAndroidBackButton(processNextHandler));
    setTimeout(() => this.presentPrivacyPolicy(), 0);
  }

  ngOnDestroy(): void {
    window.removeEventListener('clc-auth-invalid', this.invalidSessionHandler);
    this.routerEventsSubscription.unsubscribe();
    this.backButtonSubscription.unsubscribe();
  }

  private trackNavigation(url: string): void {
    const normalizedUrl = this.normalizeUrl(url);
    const currentUrl = this.navigationHistory[this.navigationHistory.length - 1];

    if (currentUrl === normalizedUrl) {
      return;
    }

    this.navigationHistory.push(normalizedUrl);
  }

  private async handleAndroidBackButton(processNextHandler: () => void): Promise<void> {
    if (!this.isTabsUrl(this.router.url)) {
      processNextHandler();
      return;
    }

    if (this.isHomeUrl(this.router.url)) {
      await App.exitApp();
      return;
    }

    if (this.navigationHistory.length > 1) {
      this.navigationHistory.pop();
      this.location.back();
      return;
    }

    await this.router.navigateByUrl(this.homeUrl, { replaceUrl: true });
  }

  private isTabsUrl(url: string): boolean {
    return this.normalizeUrl(url).startsWith('/tabs');
  }

  private isHomeUrl(url: string): boolean {
    return this.normalizeUrl(url) === this.homeUrl;
  }

  private normalizeUrl(url: string): string {
    return url.split(/[?#]/)[0];
  }

  private async handleInvalidSession(event: Event): Promise<void> {
    const status = (event as CustomEvent<{ status?: number }>).detail?.status;

    if (status !== 401) {
      return;
    }

    if (this.router.url.startsWith('/auth')) {
      return;
    }

    (await this.toast.create({
      message: 'Sesi login sudah berakhir. Silakan login ulang.',
      duration: 2800,
      color: 'danger',
    })).present();
    this.router.navigateByUrl('/auth', { replaceUrl: true });
  }

  private async presentPrivacyPolicy(): Promise<void> {
    if (this.privacyPolicy.accepted || this.privacyAlertOpen) {
      return;
    }

    this.privacyAlertOpen = true;
    const alert = await this.alert.create({
      header: 'Kebijakan Privasi QHealth',
      message: 'Dengan menggunakan aplikasi QHealth, Anda menyetujui pengumpulan dan penggunaan data seperti nama, email, nomor telepon, data janji temu, antrean, dan riwayat kunjungan untuk keperluan layanan klinik. Data digunakan untuk proses login, registrasi, booking dokter, antrean, dan riwayat layanan. QHealth tidak menjual data pribadi pengguna kepada pihak ketiga.',
      backdropDismiss: false,
      keyboardClose: false,
      buttons: [
        {
          text: 'Baca Kebijakan Privasi Lengkap',
          role: 'policy',
          handler: () => {
            this.privacyPolicy.openPolicy();
            return false;
          },
        },
        {
          text: 'Saya Setuju',
          role: 'confirm',
          handler: () => {
            this.privacyPolicy.accept();
            this.privacyAlertOpen = false;
          },
        },
      ],
    });

    await alert.present();
  }
}
