import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { AppRoutingModule } from './app-routing.module';
import {  OnInit, ViewChild } from '@angular/core';
import { FormControl, FormGroup, FormBuilder } from "@angular/forms";
import { IDayCalendarConfig, DatePickerComponent } from "ng2-date-picker";

import { Component } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import * as saveAs from 'file-saver';


@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent {
  title = 'MDModality';
  data: any;
  planData: any;
  datePickerConfig = {
    format: 'DD-MM-YYYY'
  };
  date:any

  
  constructor(private http: HttpClient ) {
    this.getPlans().subscribe(res =>{
      this.setData(res);
    })

   }
   setDate(ev){
     console.log("eve is %o", ev)
   }
   /**
    *  get the plans for a single MD 
    */
 getPlans(){
   var rnd = Math.random();
  var url = 'https://whiteboard.partners.org/esb/FLwbe/proxy.php?MDKey=test&rnd='+rnd;
  return this .http.get(url)
  }
 setData(res ) {
   this.data = res;
   console.log(this.data)
 }
 getPlanData(n){
   console.log("n is %o  key is %o", n, this.data[n]);
   var url = 'https://whiteboard.partners.org/esb/FLwbe/proxy.php?MDKey=' + n;
   this .http.get(url).subscribe(res =>{
     this. setPlanData(res)
   })
 }
 setPlanData(res){
  let areas = new Array<Array<any>>();
  this.planData = res;
  console.log("planData is %o ", this.planData)


  let tStr = "";
    Object.keys(res[0]).forEach(key2 => {
      console.log(" key2 is " + res[key2])
      tStr += res[key2]
      tStr += "\r\n"
      })
   console.log("tStr is " + tStr) 

  let blob = new Blob([tStr], { type: 'text/plain;charset=utf-8' })
  saveAs(blob, 'PlanData.csv')
  
   }
   updateFormat() {
    console.log('update')
    this.datePickerConfig = {
      ...this.datePickerConfig,
      format: 'DD-MM'
    }
  }
}
