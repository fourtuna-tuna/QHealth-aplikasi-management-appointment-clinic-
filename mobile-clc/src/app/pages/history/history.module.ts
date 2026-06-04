import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { HistoryPage } from './history.page';

const routes: Routes = [{ path: '', component: HistoryPage }];

@NgModule({ imports: [CommonModule, IonicModule, RouterModule.forChild(routes)], declarations: [HistoryPage] })
export class HistoryPageModule {}
