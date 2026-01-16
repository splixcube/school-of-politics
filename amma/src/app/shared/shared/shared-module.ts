import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonGrid, IonRow, IonCol, IonTitle, IonContent, IonHeader, IonToolbar, IonButton, IonInput, IonLabel, IonIcon, IonCard, IonCardContent, IonCardHeader, IonCardTitle, IonCardSubtitle, IonImg, IonText } from '@ionic/angular/standalone';

import { addIcons } from 'ionicons';
import { arrowBackOutline, arrowForwardOutline } from 'ionicons/icons';

addIcons({
  arrowBackOutline,
  arrowForwardOutline,
});




@NgModule({
  declarations: [],
  imports: [
    CommonModule,
    IonGrid,
    IonRow,
    IonCol,
    IonTitle,
    IonContent,
    IonHeader,
    IonToolbar,
    IonButton,
    IonInput,
    IonLabel,
    IonIcon,
    IonCard,
    IonCardContent,
    IonImg,
    IonText
  ],
  exports: [
    CommonModule,
    IonGrid,
    IonRow,
    IonCol,
    IonTitle,
    IonContent,
    IonHeader,
    IonToolbar,
    IonButton,
    IonInput,
    IonLabel,
    IonIcon,
    IonCard,
    IonCardContent,
    IonImg,
    IonText
  ]
})
export class SharedModule { }
