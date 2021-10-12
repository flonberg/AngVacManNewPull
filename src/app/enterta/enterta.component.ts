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
  userid: string;
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
  


  constructor( public datePipe: DatePipe, private activatedRoute: ActivatedRoute, private http: HttpClient ) { 
  }
  ngOnInit(): void {
    this. activatedRoute.queryParams.subscribe(params =>{
      this .userid = params['userid']
      console.log("enterta userid %o", this .userid)
    })
    this .showError = false;
    this .buttonClicked = "";
    this .overlap = false;
  }

  @Output() onDatePicked = new EventEmitter<any>();   //
  public pickDate(date: any): void {
    this. submitTA();
    this.onDatePicked.emit(date);
}

  dateRangeChange(dateRangeStart: HTMLInputElement, dateRangeEnd: HTMLInputElement) {
    var tDate = new Date(dateRangeStart.value)                              // save for editing
    if (  dateRangeEnd.value  ){
     var eDate = new Date(dateRangeEnd.value)
        this .tAparams = {startDate: this.datePipe.transform(tDate, 'yyyy-MM-dd'), 
           endDate : this.datePipe.transform(eDate, 'yyyy-MM-dd'), reason:0, note:"", userid: this .userid}
      }
    this .checkTAparams();  
  console.log("change %o", this .tAparams)
 }
 reasonSelect(ev){
    console.log("event is %o", ev) 
    if (this .tAparams)
    this .tAparams.reason= ev.value;

 }
 noteChange(ev){
  if (this .tAparams)
  this .tAparams.note= ev.target.value;
   console.log("note is %o", ev.target.value)
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
