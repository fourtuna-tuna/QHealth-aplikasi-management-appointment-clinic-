import { Injectable } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class PrivacyPolicyService {
  private readonly storageKey = 'qhealth_privacy_policy_accepted';
  readonly policyUrl = 'https://sites.google.com/view/qhealthprivacypolicy/halaman-muka';

  get accepted(): boolean {
    return localStorage.getItem(this.storageKey) === 'true';
  }

  accept(): void {
    localStorage.setItem(this.storageKey, 'true');
  }

  openPolicy(): void {
    window.open(this.policyUrl, '_blank', 'noopener,noreferrer');
  }
}
