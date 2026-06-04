import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { TabsPage } from './tabs.page';

const routes: Routes = [
  {
    path: '',
    component: TabsPage,
    children: [
      { path: 'home', loadChildren: () => import('../pages/home/home.module').then(m => m.HomePageModule) },
      { path: 'doctors', loadChildren: () => import('../pages/doctors/doctors.module').then(m => m.DoctorsPageModule) },
      { path: 'appointments', loadChildren: () => import('../pages/appointments/appointments.module').then(m => m.AppointmentsPageModule) },
      { path: 'queue', loadChildren: () => import('../pages/queue/queue.module').then(m => m.QueuePageModule) },
      { path: 'history', loadChildren: () => import('../pages/history/history.module').then(m => m.HistoryPageModule) },
      { path: 'profile', loadChildren: () => import('../pages/profile/profile.module').then(m => m.ProfilePageModule) },
      { path: '', redirectTo: 'home', pathMatch: 'full' },
    ],
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class TabsRoutingModule {}
