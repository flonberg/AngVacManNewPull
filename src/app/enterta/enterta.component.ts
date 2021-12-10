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
  WTMdate: string,
  WTMchange: number,
  WTMcoverer: string,
  WTMself: number
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
  pserviceMDs:[number]
  modeSelect:string='test'
  
  constructor( public datePipe: DatePipe, private activatedRoute: ActivatedRoute, private http: HttpClient ) { 
  }
  ngOnInit(): void {    
    this. tAparams = {
      startDate :'',
      endDate: '',
      reason: 0,
      note: '',
      userid: '',
      coverageA: 0,
      WTMdate:'',
      WTMchange: 0,
      WTMcoverer:'',
      WTMself: 0
    }
    this .pserviceMDs = [0]
    this .modeSelect = 'test'
    this. activatedRoute.queryParams.subscribe(params =>{
      this .userid = params['userid']
      this .tAparams.userid = params['userid']
      console.log("enterta userid %o", this .userid)
      if (this .userid){
        let url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDsByService.php?userid='+ this .userid
        this .http.get(url).subscribe(res =>{
          this. serviceMDs = res;
          this .makeIndex(this .serviceMDs);        
        })
      }
    })
    this .showError = false;
    this .buttonClicked = "";
    this .overlap = false;
    this .monthInc = 1;
  }
  makeIndex(obj){                                             // create an array of integers to use for index of SELECT in html    
    let k: keyof typeof obj;  
    let ind = 1;                                            
    for (k in obj) {
      this .pserviceMDs[ind] = ind++;
    }
  }

  getServiceMDs(userid){
    let url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDsByService.php?userid='+ userid
    this .http.get(url).subscribe(res =>{
      this. serviceMDs = res;
      console.log("12345 %o", this .serviceMDs)

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
 WTMparam(ev, pName){
   console.log("101 %o --- %o ", ev, pName)
   if (pName == 'WTMdateChange')
     this .tAparams.WTMchange = ev.checked ? 1 : 0
   if (pName == 'WTM_Self')
     this .tAparams.WTMself = 1
   if (pName == 'WTM_CoveringMD')
     this .tAparams.WTMself = 0
   if (pName == 'WTMdate')
    this. tAparams.WTMdate = this .datePipe.transform(new Date(ev.value), 'yyyy-MM-dd')  
  console.log("108 %o", this .tAparams)
 }
  dateRangeChange(dateRangeStart: HTMLInputElement, dateRangeEnd: HTMLInputElement) {
    var tDate = new Date(dateRangeStart.value)                              // save for editing
    this .startDateEntered = tDate;
    this .monthInc = this.whatMonthIsStartDateIn(tDate)
    if (  dateRangeEnd.value  ){
     var eDate = new Date(dateRangeEnd.value)
        this. tAparams.startDate = this .datePipe.transform(new Date(dateRangeStart.value), 'yyyy-MM-dd')   
        this. tAparams.endDate = this .datePipe.transform(new Date(dateRangeEnd.value), 'yyyy-MM-dd')   
      }
    this .checkTAparams();  
 }
 WTMchanged(WTMdate: HTMLInputElement){
   this. tAparams.WTMdate = this .datePipe.transform(new Date(WTMdate.value), 'yyyy-MM-dd')  
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
   console.log("1091091091 ")
  // this .tAparams.coverageA = ev.value
  // console.log("111 %o", this .tAparams)
 }
 noteChange(ev){
  if (this .tAparams)
  this .tAparams.note= ev.target.value;
   console.log("note is %o", this.tAparams)
 }
 /**
  * REST request of enterAngVac.php
  */
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
