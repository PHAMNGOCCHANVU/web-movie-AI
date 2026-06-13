import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-forget',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './forget.html',
  styleUrl: './forget.css',
})
export class Forget {
  step: number = 1;

  goToStep2() {
    this.step = 2;
  }

  goBack() {
    this.step = 1;
  }
}
