import { inject, Injectable } from '@angular/core';
import { CanActivate, Router, UrlTree } from '@angular/router';
import { AuthService } from './auth.service';
import { PrivacyPolicyService } from './privacy-policy.service';

@Injectable({ providedIn: 'root' })
export class AuthGuard implements CanActivate {
  private readonly auth = inject(AuthService);
  private readonly privacyPolicy = inject(PrivacyPolicyService);
  private readonly router = inject(Router);

  canActivate(): boolean | UrlTree {
    return this.auth.isAuthenticated && this.privacyPolicy.accepted ? true : this.router.parseUrl('/auth');
  }
}
