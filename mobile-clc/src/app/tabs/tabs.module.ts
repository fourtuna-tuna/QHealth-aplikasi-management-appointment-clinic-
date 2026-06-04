import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { IonicModule } from '@ionic/angular';
import { TabsRoutingModule } from './tabs-routing.module';
import { TabsPage } from './tabs.page';

@NgModule({
  imports: [CommonModule, IonicModule, TabsRoutingModule],
  declarations: [TabsPage],
})
export class TabsPageModule {}
