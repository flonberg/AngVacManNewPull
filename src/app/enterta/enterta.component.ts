import { HttpClient } from '@angular/common/http';
import { DatePipe } from '@angular/common';
import { Component, OnInit, EventEmitter, Output, Input } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ThrowStmt } from '@angular/compiler';
interface tAparams {
  startDate : string,
  endDate: string,
  reason: number,
  note: string,
  userid: string,
  coverageA: number,
  WTMdate: string
}
@Component({
  selector: 'app-enterta',
  templateUrl: './enterta.component.html',
  styleUrls: ['./enterta.component.css']
})

export class EntertaComponent implements OnInit {
  dateRangeStart: string;
  dateRangeEnd: string;
  userid: string;
  setStart: any;
  tAparams: tAparams;
  showError: boolean;
  postRes: object;
  valueEmittedFromChildComponent:any;
  buttonClicked: any;
  overlap: boolean;
  monthInc: number;
  startDateEntered: Date;
  WTMdate: Date;
  serviceMDs: {}
  


  constructor( public datePipe: DatePipe, private activatedRoute: ActivatedRoute, private http: HttpClient ) { 
  }
  ngOnInit(): void {    this. tAparams = {
    startDate :'',
    endDate: '',
    reason: 0,
    note: '',
    userid: '',
    coverageA: 0,
    WTMdate:''
  }
    this. activatedRoute.queryParams.subscribe(params =>{
      this .userid = params['userid']
      this .tAparams.userid = params['userid']
      console.log("enterta userid %o", this .userid)
      if (this .userid){
        let url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDsByService.php?userid='+ this .userid
        this .http.get(url).subscribe(res =>{
          this. serviceMDs = res;
          console.log("5252 %o", this .serviceMDs)
        })
    }
    })
    this .showError = false;
    this .buttonClicked = "";
    this .overlap = false;
    this .monthInc = 1;

  }
  getServiceMDs(userid){
    let url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDsByService.php?userid='+ userid
    this .http.get(url).subscribe(res =>{
      this. serviceMDs = res;
      console.log("5252 %o", this .serviceMDs)
    })
  }

  @Output() onDatePicked = new EventEmitter<any>();   //  THIS GOES TO PLANS.TS
  public pickDate(date: any): void {
  console.log("50 %o", date)  
    this. submitTA();
    for (let i = 0; i < 10000; i++){
      let m = i * i;
    }
    this.onDatePicked.emit(date);
}

  dateRangeChange(dateRangeStart: HTMLInputElement, dateRangeEnd: HTMLInputElement) {
    var tDate = new Date(dateRangeStart.value)                              // save for editing
    this .startDateEntered = tDate;
    this .monthInc = this.whatMonthIsStartDateIn(tDate)
console.log("56 %o", this .monthInc)   
    if (  dateRangeEnd.value  ){
     var eDate = new Date(dateRangeEnd.value)
        this. tAparams.startDate = this .datePipe.transform(new Date(dateRangeStart.value), 'yyyy-MM-dd')   
        this. tAparams.endDate = this .datePipe.transform(new Date(dateRangeEnd.value), 'yyyy-MM-dd')   
      }
    this .checkTAparams();  

 }
 WTMchanged(WTMdate: HTMLInputElement){
   this. tAparams.WTMdate = this .datePipe.transform(new Date(WTMdate.value), 'yyyy-MM-dd')  
   console.log("change %o", this .tAparams)
 }
 whatMonthIsStartDateIn(startDate){
   let thisMonth = new Date();
   var lastDate = new Date(thisMonth.getFullYear(), thisMonth.getMonth() + 2, 0);
   if (startDate < lastDate)
    return 0;
  return 1;
 }
 reasonSelect(ev){
    console.log("event is %o", ev) 
    if (this .tAparams)
    this .tAparams.reason= ev.value;

 }
 covererSelect(ev){
   console.log("1091091091 %o", ev)
   this .tAparams.coverageA = ev.value
   console.log("111 %o", this .tAparams)
 }
 noteChange(ev){
  if (this .tAparams)
  this .tAparams.note= ev.target.value;
   console.log("note is %o", this.tAparams)
 }
 submitTA(){                                                                  // need to put in full error checking. 
  this .checkTAparams();
  var jData = JSON.stringify(this .tAparams)
  var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/enterAngVac.php';
  this .http.post(url, jData).subscribe(ret=>{
    this .postRes = (ret)
    console.log("75' ret from enterAndGac %o",this .postRes)
    if (this. postRes['result'] == 0)
      this .overlap = true;
      }
    )
 }
 checkTAparams(){
  if (!this .tAparams){
    this .showError = true;
    return;
  }
  if (this .tAparams.startDate.length < 2 || this .tAparams.endDate.length < 2  || this .tAparams.reason == 0 ){
    this .showError = true;  
    return
  }
  this .showError = false;
 }

}
