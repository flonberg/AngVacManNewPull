import { HttpClient } from '@angular/common/http';
import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
interface tAparams {
  startDate : string,
  endDate: string,
  reason: number,
  note: string

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
  

  constructor( public datePipe: DatePipe, private activatedRoute: ActivatedRoute, private http: HttpClient ) { 

  }
  ngOnInit(): void {
    this. activatedRoute.queryParams.subscribe(params =>{
      this .userid = params['userid']
      console.log("enterta userid %o", this .userid)
    })
    this .showError = false;
  }
  dateRangeChange(dateRangeStart: HTMLInputElement, dateRangeEnd: HTMLInputElement) {
    var tDate = new Date(dateRangeStart.value)
    if (  dateRangeEnd.value  ){
     var eDate = new Date(dateRangeEnd.value)
        this .tAparams = {startDate: this.datePipe.transform(tDate, 'yyyy-MM-dd'), 
           endDate : this.datePipe.transform(eDate, 'yyyy-MM-dd'), reason:0, note:""}
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
 submitTA(){
  this .checkTAparams();
  var jData = JSON.stringify(this .tAparams)
  var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getVacs.php';
  return this .http.post(url, jData).subscribe(ret=>
    this .postRes =  ret)
  console.log(jData)
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
