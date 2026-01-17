import { Routes } from '@angular/router';

export const routes: Routes = [
  
  {
    path: 'welcome',
    loadComponent: () => import('./pages/welcome/welcome.page').then( m => m.WelcomePage)
  },
  {
    path: '',
    redirectTo: 'welcome',
    pathMatch: 'full',
  },
  
  {
    path: 'sign-in',
    loadComponent: () => import('./pages/sign-in/sign-in.page').then( m => m.SignInPage)
  },
  {
    path: 'verify-otp',
    loadComponent: () => import('./pages/verify-otp/verify-otp.page').then( m => m.VerifyOtpPage)
  },
  {
    path: 'home',
    loadComponent: () => import('./home/home.page').then((m) => m.HomePage),
  },
  {
    path: 'paywall',
    loadComponent: () => import('./dashboard/paywall/paywall.page').then( m => m.PaywallPage)
  }
];
