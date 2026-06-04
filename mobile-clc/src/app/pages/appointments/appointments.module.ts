import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { AppointmentsPage } from './appointments.page';

const routes: Routes = [{ path: '', component: AppointmentsPage }];

@NgModule({ imports: [CommonModule, FormsModule, IonicModule, RouterModule.forChild(routes)], declarations: [AppointmentsPage] })
export class AppointmentsPageModule {}
