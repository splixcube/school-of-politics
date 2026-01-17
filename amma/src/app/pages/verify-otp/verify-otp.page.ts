import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared/shared-module';
import { NgOtpInputModule } from 'ng-otp-input';
import { Router } from '@angular/router';

@Component({
  selector: 'app-verify-otp',
  templateUrl: './verify-otp.page.html',
  styleUrls: ['./verify-otp.page.scss'],
  standalone: true,
  imports: [SharedModule, NgOtpInputModule]
})
export class VerifyOtpPage implements OnInit {
  otpConfig = {
    length: 6,
    allowNumbersOnly: true,
    inputStyles: {
      'width': '50px',
      'height': '50px',
      'border-radius': '8px',
      'background-color': '#FFEDD5',
      'border': '1px solid #FDBA74',
      'color': '#000',
      'font-size': '20px',
      'font-weight': '600'
    },
    containerClass: 'otp-container'
  };

  otpValue: string = '';

  constructor(private router: Router) { }

  ngOnInit() {
  }

  onOtpChange(otp: string) {
    this.otpValue = otp;
    console.log('OTP entered:', otp);
  }

  navigateToHome() {
    this.router.navigate(['/home']);
  }

}
