import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { SharedModule } from 'src/app/shared/shared/shared-module';

@Component({
  selector: 'app-sign-in',
  templateUrl: './sign-in.page.html',
  styleUrls: ['./sign-in.page.scss'],
  standalone: true,
  imports: [SharedModule]
})
export class SignInPage implements OnInit {

  constructor(private router: Router) { }

  ngOnInit() {
  }

  navigateToVerifyOtp() {
    this.router.navigate(['/verify-otp']);
  }

}
