import { Component } from '@angular/core';
import { addIcons } from 'ionicons';
import { calendarOutline, checkmarkCircleOutline, documentTextOutline, homeOutline, medkitOutline, personOutline, ticketOutline } from 'ionicons/icons';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss'],
  standalone: false,
})
export class AppComponent {
  constructor() {
    addIcons({ homeOutline, medkitOutline, calendarOutline, ticketOutline, documentTextOutline, personOutline, checkmarkCircleOutline });
  }
}
