import { AppComponent } from './../app.component';
import { HttpClient } from '@angular/common/http';
import { analyzeAndValidateNgModules } from '@angular/compiler';
import { Component, EventEmitter, OnInit, Output } from '@angular/core';

@Component({
  selector: 'app-plans',
  templateUrl: './plans.component.html',
  styleUrls: ['./plans.component.css'],
 
})
export class PlansComponent implements OnInit {
  panelOpenState: boolean;
  vacData: any;
  users: any;
  calDates: Date[];
  dayNum: number;
  vacDays: number;
  dayOfMonth: number;
  setStart: any;
  currentItem:any;
  prop1: any;
  @Output() editTAee= new EventEmitter()
  
  constructor(private http: HttpClient) { 
    
  }

  ngOnInit(): void {
    this .dayOfMonth = new Date().getDate();
    this. dayNum = 1;
    this. vacDays = 1;
    this .currentItem = "test"

    this .vacData = Array();
    this.getVacs().subscribe(res =>{
      console.log(" res is %o", res)
      this.getUsers().subscribe(rusers=>{
        this .users = rusers;
    //    console.log("41 this.userw %o", this .users)
      })

     this .vacData = res;
      console.log("vacData is %o", this. vacData)
    })
    this. setCalDates();
  }
 public doSomething(ev){
    console.log("49 in PlansComponent.ts ev %o", ev)
 }
 dataFromChild:any
 eventFromChild(data) {
   this.dataFromChild = data;
   console.log("53")
 }
  editTA(vac){
    console.log("vac is %o", vac)
    this. editTAee.emit(vac);
  }

  getDateClass(d: Date){
    let today = new Date()
    let dDate = d.getDate();
    let todayDate = today.getDate();
    if (d.getDate() === today.getDate()  && 
       d.getMonth() === today.getMonth()  &&
       d.getFullYear() === today.getFullYear()) 
      return 'todayCell'
    if (d.getDay() == 6  || d.getDay() == 0)
        return 'weekend'
  }
  getClass(){
    if (this. dayNum == this .dayOfMonth)
    return 'todayCell'
  }

  showIp(ip: number){
    return ip;
  }
  getDayNum(){
    return this. dayNum;
  }
  zeroDayNum(){
    this. dayNum = -1;
  }
  addVacDays(n: number){
    this. vacDays = this. vacDays + n;
  }
  incDay(n: number){
    this. dayNum = this. dayNum + n;
    if (this. dayNum == this .dayOfMonth -1)
      return 'todayCell'
    return this. dayNum +1;
  }
  incDay1(n: number, m: number){
    this. dayNum = this. dayNum + n;
    if ( this. dayNum + m + 1 == this. dayOfMonth)
      return "todayCell"
   // return this. dayNum + m + 1;
  }
  incDay2(n: number, m: number){
    this. dayNum = this. dayNum + n;
    return this. dayNum + m + 1;
  }

  getVacs(){
    var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getVacs.php';
    var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDtAs.php';
    return this .http.get(url)
  }
  getUsers(){
    var url = 'https://ion.mgh.harvard.edu/cgi-bin/imrtqa/getUsers.php';
    return this .http.get(url)
  }
  setUsers(res){
    this. users = res;
  }
  setData(res ) {
    this.getUsers().subscribe(res =>{
      this.setUsers(res);
      console.log("121 usere is %o", this .setUsers)
    })
      for(const vr in res){
        var uKey = res[vr][0]['userid']
        if (this .users)
           console.log( "vr is %o", this. users[uKey]) 
     //   console.log("lastName is %o", this. users[res[vr][0]['userid'] ])
      }
      this.vacData = res;
      console.log(this.vacData)
 }
 counter(n){
      var ar = Array();
      for (var i=0; i < n; i++ ){
        ar[i] = i;
      }
      return ar;
  }
  setCalDates(){
      var date = new Date();
      var daysInMonth0 = date.getDate();
      var firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
      var lastDay = new Date(date.getFullYear(), date.getMonth() + 2, 0);
      this. calDates = Array();
      console.log("daysInMonth0 is %o", daysInMonth0);
      var i = 0;
      do {
        var cDay = new Date(firstDay.valueOf());
        this. calDates[i++] = cDay;
        firstDay.setDate(firstDay.getDate() + 1);
      }
      while (firstDay <= lastDay)
      var test = this .calDates[2].getDate();
        console.log("calDays is %o", test)

    }
  daysTillEnd(val){
      const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
      var endDate = new Date(val['endDate'])
      var calEndDate = new Date( this. calDates[this. calDates.length-1])
      var diff =Math.round( (calEndDate.valueOf() - endDate.valueOf())/oneDay);
     return diff;
    }
  daysBetween(val1, val2){
    const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
    var endDate = new Date(val1['endDate'])
    var calEndDate = new Date( val2['startDate'])
    var diff =Math.round( (calEndDate.valueOf() - endDate.valueOf())/oneDay);

    return diff -1;
  }  
  daysBetweenX(val1, val2){

    const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
    var endDate = new Date(val1['endDate'])
    var calEndDate = new Date( val2['startDate'])
    var diff =Math.round( (calEndDate.valueOf() - endDate.valueOf())/oneDay);

    return diff;
  }  
}
