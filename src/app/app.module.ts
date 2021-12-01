import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { HttpClientModule } from '@angular/common/http';


import { DpDatePickerModule } from "ng2-date-picker";
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { MatSliderModule } from '@angular/material/slider';
import {MatDatepickerModule, MatDateRangeInput} from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatLabel } from '@angular/material/form-field';
import {MatCheckboxModule} from '@angular/material/checkbox';
import {MatRadioModule} from '@angular/material/radio';
import {MatRadioGroup} from '@angular/material/radio';
import {MatIconModule} from '@angular/material/icon'; 
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import {MatSelectModule} from '@angular/material/select';
import {MatDialogModule} from '@angular/material/dialog';
import { DatePipe } from '@angular/common';
import { PlansComponent } from './plans/plans.component';
import {MatExpansionModule} from '@angular/material/expansion';
import { EditTAComponent } from './edit-ta/edit-ta.component';
import { RouterModule } from '@angular/router';
import { EntertaComponent } from './enterta/enterta.component';
@NgModule({
  declarations: [
    AppComponent,
    PlansComponent,
    EditTAComponent,
    EntertaComponent  
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    HttpClientModule,
    MatSliderModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatFormFieldModule,
    MatExpansionModule,
    MatSelectModule,
    MatDialogModule,
    MatInputModule,
    MatIconModule,
    MatRadioModule,
    MatCheckboxModule,


    BrowserAnimationsModule,
    RouterModule.forRoot([
      { path: '**', component: AppComponent}
    ])
  ],

  providers: [DatePipe],
  bootstrap: [AppComponent]
})
export class AppModule { }
