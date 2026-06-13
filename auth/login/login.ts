import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login {
  isLoginMode = true;
  email = '';
  password = '';
  errorMessage = '';

  constructor(private authService: AuthService, private router: Router) {}

  toggleMode() {
    this.isLoginMode = !this.isLoginMode;
  }

  onLogin() {
    const user = this.authService.login(this.email, this.password);

    if (!user) {
      this.errorMessage = 'Sai tên đăng nhập hoặc mật khẩu.';
      return;
    }

    this.errorMessage = '';
    this.router.navigate([user.role === 'ADMIN' ? '/admin' : user.role === 'ARTIST' ? '/artist' : '/']);
  }
}
