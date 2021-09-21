import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { AppRoutingModule } from './app-routing.module';
import {  OnInit, ViewChild } from '@angular/core';
import { FormControl, FormGroup, FormBuilder } from "@angular/forms";
import { IDayCalendarConfig, DatePickerComponent } from "ng2-date-picker";

import { Component } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import * as saveAs from 'file-saver';
import { DatePipe } from '@angular/common';
import { MAT_DATE_RANGE_INPUT_PARENT } from '@angular/material/datepicker/date-range-input-parts';


@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent {
  title = 'MDModality';
  data: any;
  planData: any;
  MDKeySelected: number;
  dateRangeStart: string;
  dateRangeEnd: string;
  MDName: string;
  date:any

  
  constructor(private http: HttpClient, public datePipe: DatePipe) {
    console.log(" const ");
    this.getMDs().subscribe(res =>{
      this.setData(res);
    })

   }

   setDate(start, end){
     console.log("eve is %o", start)
   }
   /**
    *  get the plans for a single MD, ION-endPt looks for 'test' to make it return MD list 
    */
 
   getMDs(){
  var url = 'https://whiteboard.partners.org/esb/FLwbe/proxy.php?MDKey=test';
  return this .http.get(url)
  }
 setData(res ) {
   console.log("111")
   this.data = res;
   console.log(this.data)
 }
 getPlanData(n){
   this .MDName = n;
   this .MDKeySelected = this .data[n];
   console.log("n is %o  key is %o", n, this.data[n]);
   if (this .dateRangeStart)
    var url = 'https://whiteboard.partners.org/esb/FLwbe/proxy.php?MDKey=' + this.data[n] + '&startDate=' + this .dateRangeStart +
                                                                                          '&endDate=' + this .dateRangeEnd;
        console.log('url 57 is %o', url)
   this .http.get(url).subscribe(res =>{
     this. setPlanData(res)
   })
 }

 dateRangeChange(dateRangeStart: HTMLInputElement, dateRangeEnd: HTMLInputElement) {
   var tDate = new Date(dateRangeStart.value)
    this .dateRangeStart = this.datePipe.transform(tDate, 'yyyy-MM-dd')
   if (  dateRangeEnd.value  ){
    var eDate = new Date(dateRangeEnd.value)
   this .dateRangeEnd = this.datePipe.transform(eDate, 'yyyy-MM-dd')
 }

}
 setPlanData(res){
  let areas = new Array<Array<any>>();
  this.planData = res;
  console.log("planData is %o ", this.planData)
  let tStr = this .MDName + "," + this .dateRangeStart + "," + this.dateRangeEnd + "\r\n";      // put in MdName, StartDate and EndDate
  tStr += "\r\n Days, 1,2,3,4,5,6,7,8,9,10,11,12,>12,\r\n";
    Object.keys(res[0]).forEach(key2 => {
      console.log(" key2 is " + res[key2])
      if (res[key2]){
      tStr += res[key2]
      tStr += "\r\n"
      }
      })
   console.log("tStr is " + tStr) 

  let blob = new Blob([tStr], { type: 'text/plain;charset=utf-8' })
  saveAs(blob, 'PlanData.csv')
  
   }

}
