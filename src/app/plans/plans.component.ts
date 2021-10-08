import { AppComponent } from './../app.component';
import { HttpClient } from '@angular/common/http';
import { DatePipe } from '@angular/common';
import { analyzeAndValidateNgModules } from '@angular/compiler';
import { Component, EventEmitter, OnInit, Output } from '@angular/core';
import { MatDatepickerInputEvent } from '@angular/material/datepicker';
import { throwError } from 'rxjs';
interface tAparams {
  startDate? : string,
  endDate?: string,
  reasonIdx?: number,
  note?: string,
  vidx: string;
}

@Component({
  selector: 'app-plans',
  templateUrl: './plans.component.html',
  styleUrls: ['./plans.component.css'],
 
})
export class PlansComponent implements OnInit {
  panelOpenState: boolean;
  vacData: any;
  vacEdit: any;
  users: any;
  calDates: Date[];                                                         // the dates used to draw the calendat
  dayNum: number;
  vacDays: number;
  dayOfMonth: number;
  setStart: any;
  currentItem:any;
  prop1: any;
  showEdit: boolean;
  tAparams: tAparams;
  reasonIdx: string;
  reason: string;
  dayArray: any;
  startDateConvent: string;
  
  constructor(private http: HttpClient, private datePipe: DatePipe ) { }

  ngOnInit(): void {
    this .dayOfMonth = new Date().getDate();
    console.log("46 dayOgMong %o", this.dayOfMonth)
    this. dayNum = 1;
    this. vacDays = 1;
    this .currentItem = "test"
    this .showEdit = false;
    this .reasonIdx = "1";
    this .reason = 'Personal Vacation'

    this .vacData = Array();
    this.getVacs().subscribe(res =>{
      console.log(" res is %o", res)
      this.getUsers().subscribe(rusers=>{
        this .users = rusers;
      //    console.log("41 this.userw %o", this .users)
        })
      this .vacData = res;
        console.log("vacData is %o", this. vacData)
      for (const tRow in this. vacData){
console.log("61 tRos is %o", tRow)        
        this.makeDaysOfRow(this .vacData[tRow])
        this .vacData[tRow][9] = (this .dayArray);
      }  
      console.log("64 vacData is %o", this .vacData)
    })

    this. setCalDates();
  }
private makeDaysOfRow(vacRow){
  this .dayArray = [[]];
  for (let i = 0; i < vacRow[0]['daysTillStartDate']; i++){
    this. dayArray[0][i] = i + 1;
  }
// go to a date after the end of the tA  
  let v1 = vacRow[0]['daysTillStartDate'] + vacRow[0]['vacLength'] 
  console.log("81 v1 is %o", v1)
  if (!vacRow[1]){                                      // this is the last tA in the row
    this .makeTillEndDays(v1,1);                        // fill out the rest of the dayNum
    return;                                             // don't do anything else
  }
  // If there is a SECOND tA in the row, find the days between the first and second tA
  let d1 = this.daysBetweenA(vacRow[0]['endDate'], vacRow[1]['startDate']) -1
  for (let k=0; k < d1; k++){                           // loop and push required dayNums
    v1++;                                                                           
    if (!this .dayArray[1]){
      this .dayArray[1] = Array();
      this .dayArray[1][0] = v1;
    }
    else
      this .dayArray[1].push(v1);                         // into the dataStruct
  }
  v1 += (vacRow[1]['vacLength'] )                       // increment to end of second tA  tA[1]                  
  if (!vacRow[2]){                                      // if this is the LAST tA
    this .makeTillEndDays(v1,2);                        // fill out the rest of the days
    return;
  }
  // if there is a THIRD tA
  let d2 = this.daysBetweenA(vacRow[1]['endDate'], vacRow[2]['startDate']) -1
  for (let k=0; k < d2; k++){
    v1++;
    if (!this .dayArray[2]){
      this .dayArray[2] = Array();
      this .dayArray[2][0] = v1;
    }
    else
      this .dayArray[2].push(v1);
  }
  v1 += vacRow[2]['vacLength']
  if (!vacRow[3]){
    this .makeTillEndDays(v1,3);
    return;
  }
    // if there is a FOURTH tA
    let d3 = this.daysBetweenA(vacRow[2]['endDate'], vacRow[3]['startDate']) -1
    for (let k=0; k < d3; k++){
      v1++;
      if (!this .dayArray[3]){
        this .dayArray[3] = Array();
        this .dayArray[3][0] = v1;
      }
      else
        this .dayArray[3].push(v1);
    }
    v1 += vacRow[3]['vacLength']
    if (!vacRow[4]){
      this .makeTillEndDays(v1,4);
      return;
    }
}  

private makeTillEndDays(v1, n ){
  let tillEnd = 31 - v1;
  if (n == 4)
console.log("117  %o  === %o", v1 , n)  
  for (let k=0; k < tillEnd; k++){
    v1++
    if (!this .dayArray[n]){
      this .dayArray[n] = Array()
      this. dayArray[n][0] = v1;
    }
    else
      this .dayArray[n].push(v1);
  }
  if (n == 4)
  console.log("127 dayArray is %o", this .dayArray[n])
}

private  editDate(type: string, ev: MatDatepickerInputEvent<Date>) {
    console.log("53 %o --%o", type, ev.value)
    let dateString = this.datePipe.transform(ev.value, 'yyyy-MM-dd')
    if (type.indexOf("start") >= 0){
      this .tAparams.startDate = dateString;
    }
    if (type.indexOf("end") >= 0){
      this .tAparams.endDate = dateString;
    }
    console.log("103 %o", this .tAparams)
}
private deleteTa(ev){
  this .tAparams.reasonIdx = 99;
  console.log("147 tAparams %o", this.tAparams)
  this .saveEdits();
}
private saveEdits() {
  var jData = JSON.stringify(this .tAparams)                        // form the data to pass to php script
  var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/editAngVac.php';  // set endPoint
  this .http.post(url, jData).subscribe(res =>{                     // do the http.post
    this .getVacs().subscribe(get => {                              // reload the vacData
      this .vacData = get;                                          // store the new vacData
      for (const tRow in this. vacData){
        this.makeDaysOfRow(this .vacData[tRow])
        this .vacData[tRow][9] = (this .dayArray);
      }  
   this .showEdit = false; 
   console.log("152 vacData is %o", this .vacData)
    })
  })
}
private editReasonIdx(ev){
  console.log("66 %o", ev)
  
}
private toConventFormat(dateStr){
  let date = new Date(dateStr)
  return this.datePipe.transform(dateStr, 'MM-d-yyyy')
}
 private showEditFunc(vacEdit){
  this .startDateConvent = this .toConventFormat(vacEdit.startDate)
   console.log("49 %o", vacEdit)
   this .tAparams ={} as tAparams;
   this .tAparams.vidx  = vacEdit.vidx;
   this .vacEdit = vacEdit;

  
   this. showEdit = true;
 } 
 public doSomething(ev){                                            // access point for enterData component
    console.log("49 in PlansComponent.ts ev %o", ev)
    let startDate = new Date(ev.startDate)
    this .tAparams.startDate = this.datePipe.transform(startDate, 'MM-dd-YYYY')
    this .showEdit = false;
    this .getVacs().subscribe(res =>{
      this. vacData = res;
      for (const tRow in this. vacData){
        this.makeDaysOfRow(this .vacData[tRow])
        this .vacData[tRow][9] = (this .dayArray);
      }  
    })
 }

 public newItemEvent(ev){
   console.log("53")
 }
 dataFromChild:any
 public eventFromChild(data) {
   console.log("53")
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
  getClass(n){
    if (n == this .dayOfMonth)
    return 'todayCell'
  }

 
  zeroDayNum(){                                         // reset the dayNum for each row of Cal
    this. dayNum = 0;
  }
  //addVacDays(n: number){
  //  this. vacDays = this. vacDays + n;
  //}
  testDay(n:number){
  //  console.log("140 %o", this .dayNum)
    this. dayNum = this. dayNum + n;
  }
  testDay1(n:number){

    this. dayNum = this. dayNum + n;
 
  }
  incDay(n: number){                                  // increment the dayNum of a Cal call. 
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
 // incDay2(n: number, m: number){
  //  this. dayNum = this. dayNum + n;
  //  return this. dayNum + m + 1;
 // }

  getVacs(){
    var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getVacs.php';
    var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDtAs.php';
    return this .http.get(url)
  }
  getUsers(){
    var url = 'https://ion.mgh.harvard.edu/cgi-bin/imrtqa/getUsers.php';
    return this .http.get(url)
  }
//  setUsers(res){
//    this. users = res;
//  }
  setData(res ) {
    this.getUsers().subscribe(res =>{
      this .users = res;
    })
    this.vacData = res;
    console.log(this.vacData)
 }
 counter(n){                                            // used for looper in Calendar
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
      var i = 0;
      do {
        var cDay = new Date(firstDay.valueOf());
        this. calDates[i++] = cDay;
        firstDay.setDate(firstDay.getDate() + 1);
      }
      while (firstDay <= lastDay)
    }
  daysTillEnd(val){
      const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
      var endDate = new Date(val['endDate'])
      var calEndDate = new Date( this. calDates[this. calDates.length-1])
      var diff =Math.round( (calEndDate.valueOf() - endDate.valueOf())/oneDay);
     return diff;
    }
  daysBetween(val1, val2){                        // used by counter function
    const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
    var endDate = new Date(val1['endDate'])
    var calEndDate = new Date( val2['startDate'])
    var diff =Math.round( (calEndDate.valueOf() - endDate.valueOf())/oneDay);
    return diff -1;
  }  
  daysBetweenA(val1, val2){
    const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
    var d1 = new Date(val1)
    var d2= new Date( val2)
    var tst = d2.valueOf() - d1.valueOf();
    var diff =Math.round( (d2.valueOf() - d1.valueOf())/oneDay);
    return diff ;
  } 

}
