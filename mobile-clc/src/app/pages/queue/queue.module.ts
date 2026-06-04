import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { QueuePage } from './queue.page';

const routes: Routes = [{ path: '', component: QueuePage }];

@NgModule({ imports: [CommonModule, IonicModule, RouterModule.forChild(routes)], declarations: [QueuePage] })
export class QueuePageModule {}
