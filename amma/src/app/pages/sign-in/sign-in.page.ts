import { Component, OnInit } from '@angular/core';
import { SharedModule } from 'src/app/shared/shared/shared-module';

@Component({
  selector: 'app-sign-in',
  templateUrl: './sign-in.page.html',
  styleUrls: ['./sign-in.page.scss'],
  standalone: true,
  imports: [SharedModule]
})
export class SignInPage implements OnInit {

  constructor() { }

  ngOnInit() {
  }

}
