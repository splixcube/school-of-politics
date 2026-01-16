import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent, IonHeader, IonTitle, IonToolbar, IonGrid } from '@ionic/angular/standalone';
import { SharedModule } from 'src/app/shared/shared/shared-module';
import { Router } from '@angular/router';

@Component({
  selector: 'app-welcome',
  templateUrl: './welcome.page.html',
  styleUrls: ['./welcome.page.scss'],
  standalone: true,
  imports: [SharedModule, CommonModule]
})
export class WelcomePage implements OnInit {
  currentSlide: number = 0;
  totalSlides: number = 3;

  constructor(private router: Router) { }

  ngOnInit() {
  }

  get slidesArray(): number[] {
    return Array(this.totalSlides).fill(0).map((_, i) => i);
  }

  nextSlide() {
    if (this.currentSlide < this.totalSlides - 1) {
      this.currentSlide++;
    } else {
      this.router.navigate(['/sign-in']);
    }
  }

  previousSlide() {
    if (this.currentSlide > 0) {
      this.currentSlide--;
    }
  }

}
