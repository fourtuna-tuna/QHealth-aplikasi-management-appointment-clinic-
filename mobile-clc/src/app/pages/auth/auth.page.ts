import { Component, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Router } from '@angular/router';
import { ToastController } from '@ionic/angular';
import { Observable } from 'rxjs';
import { AuthService } from '../../services/auth.service';
import { PrivacyPolicyService } from '../../services/privacy-policy.service';

@Component({
  selector: 'app-auth',
  templateUrl: './auth.page.html',
  styleUrls: ['./auth.page.scss'],
  standalone: false,
})
export class AuthPage {
  private readonly auth = inject(AuthService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastController);
  readonly privacyPolicy = inject(PrivacyPolicyService);
  mode: 'login' | 'register' | 'forgot' | 'reset' = 'login';
  form = { name: '', email: '', token: '', password: '', password_confirmation: '', phone: '', birth_date: '', gender: 'Perempuan', address: '' };
  loading = false;

  constructor() {
    const params = this.route.snapshot.queryParamMap;
    const mode = params.get('mode');

    if (mode === 'reset' && params.get('email') && params.get('token')) {
      this.mode = 'reset';
      this.form.email = params.get('email') || '';
      this.form.token = params.get('token') || '';
    }

    if (this.auth.isAuthenticated && this.privacyPolicy.accepted && this.mode !== 'reset') {
      this.router.navigateByUrl('/tabs/home', { replaceUrl: true });
    }
  }

  openForgotPassword(): void {
    this.mode = 'forgot';
    this.form.password = '';
    this.form.password_confirmation = '';
  }

  backToLogin(): void {
    this.mode = 'login';
    this.form.token = '';
    this.form.password = '';
    this.form.password_confirmation = '';
  }

  submit(): void {
    if (!this.privacyPolicy.accepted) {
      this.toast.create({ message: 'Setujui Kebijakan Privasi QHealth terlebih dahulu.', duration: 2200, color: 'warning' })
        .then(toast => toast.present());
      return;
    }

    this.loading = true;
    const request: Observable<unknown> = this.mode === 'login'
      ? this.auth.login(this.form.email, this.form.password)
      : this.mode === 'register'
        ? this.auth.register(this.form)
        : this.mode === 'forgot'
          ? this.auth.forgotPassword(this.form.email)
          : this.auth.resetPassword({
            email: this.form.email,
            token: this.form.token,
            password: this.form.password,
            password_confirmation: this.form.password_confirmation,
          });

    request.subscribe({
      next: async () => {
        this.loading = false;
        if (this.mode === 'forgot') {
          (await this.toast.create({ message: 'Link reset password berhasil dikirim ke email Anda', duration: 2200, color: 'primary' })).present();
          this.backToLogin();
          return;
        }

        if (this.mode === 'reset') {
          (await this.toast.create({ message: 'Password berhasil direset', duration: 1800, color: 'primary' })).present();
          this.auth.clearAuth();
          this.backToLogin();
          this.router.navigateByUrl('/auth', { replaceUrl: true });
          return;
        }

        if (this.mode === 'register') {
          (await this.toast.create({ message: 'Akun berhasil dibuat. Selamat datang di QHealth clinic', duration: 1800, color: 'primary' })).present();
          this.router.navigateByUrl('/tabs/home', { replaceUrl: true });
          return;
        }

        (await this.toast.create({ message: 'Selamat datang di QHealth clinic', duration: 1400, color: 'primary' })).present();
        this.router.navigateByUrl('/tabs/home', { replaceUrl: true });
      },
      error: async (error: { userMessage?: string }) => {
        this.loading = false;
        (await this.toast.create({ message: error.userMessage || 'Tidak bisa terhubung ke API Laravel', duration: 3200, color: 'danger' })).present();
      },
    });
  }
}
