import { Component, inject, OnDestroy, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-splash',
  templateUrl: './splash.page.html',
  styleUrls: ['./splash.page.scss'],
  standalone: false,
})
export class SplashPage implements OnInit, OnDestroy {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private redirectTimer?: ReturnType<typeof setTimeout>;

  ngOnInit(): void {
    this.redirectTimer = setTimeout(() => {
      const target = this.auth.isAuthenticated ? '/tabs/home' : '/auth';
      this.router.navigateByUrl(target, { replaceUrl: true });
    }, 2400);
  }

  ngOnDestroy(): void {
    if (this.redirectTimer) {
      clearTimeout(this.redirectTimer);
    }
  }
}
