import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { HttpClientModule } from '@angular/common/http';
import { DatepickerModule } from 'ng2-datepicker';
import { ReactiveFormsModule } from '@angular/forms';

import { DpDatePickerModule } from "ng2-date-picker";

@NgModule({
  declarations: [
    AppComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    HttpClientModule,
    DatepickerModule, BrowserModule, ReactiveFormsModule, DpDatePickerModule 
  ],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
