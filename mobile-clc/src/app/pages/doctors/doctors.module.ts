import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { DoctorsPage } from './doctors.page';

const routes: Routes = [{ path: '', component: DoctorsPage }];

@NgModule({ imports: [CommonModule, IonicModule, RouterModule.forChild(routes)], declarations: [DoctorsPage] })
export class DoctorsPageModule {}
