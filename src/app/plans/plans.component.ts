import { AppComponent } from './../app.component';
import { HttpClient } from '@angular/common/http';
import { DatePipe } from '@angular/common';
import { analyzeAndValidateNgModules } from '@angular/compiler';
import { Component, EventEmitter, OnInit, Output } from '@angular/core';
import { MatDatepickerInputEvent } from '@angular/material/datepicker';
import { throwError } from 'rxjs';
import { connectableObservableDescriptor } from 'rxjs/internal/observable/ConnectableObservable';
interface tAparams {
  startDate? : string,
  endDate?: string,
  reasonIdx?: number,
  note?: string,
  vidx: string;
}
interface calParams {
  firstMonthName: string,
  secondMonthName: string,
  daysInFirstMonth:number,
  daysInSecondMonth: number,
  lastDateOnCal: Date,
}

@Component({
  selector: 'app-plans',
  templateUrl: './plans.component.html',
  styleUrls: ['./plans.component.css'],
 
})
export class PlansComponent implements OnInit {
  // panelOpenState: boolean;
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
  endDateConvent: string;
  v1: number;
  numDaysOnCal: number;
  monthInc: number;
  getVacURL: string; 
  firstTest: number;
  calParams: calParams;


  
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
    this .monthInc = 0;
    this. getVacURL = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDtAs.php?adv='+ this.monthInc;
    this .numDaysOnCal = 61;
    this .firstTest = 0;
    this .vacData = Array();
    this. setCalDates();
    this .getTheData();
  }      
  /**
   * Get tA data from 242.  The URL has GET params det'ing the monthInc, which det's the 2-month data acquisition interval
   */  
  public getTheData(){
    console.log("68 url is %o", this .getVacURL)
    this .http.get(this .getVacURL).subscribe(res =>{
      this .vacData = res;
        console.log("62 vacData is %o", this. vacData)
      for (const tRow in this. vacData){ 
        this.makeDaysOfRow(this .vacData[tRow])               // fill out the row of boxes for the Calenday
        this .vacData[tRow][9] = (this .dayArray);            // dayArray is array of dayNumbers used to det the TODAY box      

      }  
    })
  }   
   /**
  * Used by enterTa to signal a new tA has been added and we need to reload the data. 
  * @param ev 
  */
 public refreshData(date: any){                                     // <app-enterta (onDatePicked) = "refreshData($event)"  ></app-enterta>
  this .showEdit = false;
  let tst = this .howManyMonthForward(date) -1
  this .monthInc = tst
  this .advanceMonth(tst)
 
 /*
  this .setCalDates
 // console.log('298 Picked date: %o is on cal is %o ', date, tst);
  this .getVacURL = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDtAs.php?adv='+ tst
  this .http.get(this .getVacURL).subscribe(res =>{
    this. vacData = res;
    for (const tRow in this. vacData){
      this.makeDaysOfRow(this .vacData[tRow])
      this .vacData[tRow][9] = (this .dayArray);
    }  
  })

  */
}               
private howManyMonthForward(date){
  let n = 0
  let testDate = this .calParams.lastDateOnCal
  if (date < this .calParams.lastDateOnCal)
    return n;
  do {
    n++;
    testDate = new Date(testDate.getFullYear(), testDate.getMonth() + n, 0)               // last date of next month
console.log("115 testDate %o", testDate)    
  } while  (date > testDate && n < 10) ;
  return n 
}                         
  /**
   * Main loop for filling out the Calendar Row for a TimeAway, fills in all the day boxes
   * @param vacRow 
   * @returns 
   */
private makeDaysOfRow(vacRow){
  this .dayArray = [[]];
  let dBC = this. daysBeforeCalcStart(vacRow[0])          // if first tA starts in earlier month
    for (let i = 0; i < vacRow[0]['daysTillStartDate']; i++){
      this. dayArray[0][i] = i + 1;
  }
  // go to a date after the end of the tA  
    this .v1 = vacRow[0]['daysTillStartDate'] + vacRow[0]['vacLength'] 
    if (!vacRow[1]){                                      // this is the last tA in the row
      this .makeTillEndDays(this .v1,1);                        // fill out the rest of the dayNum
      this .makeTillEndBoxes(vacRow[0])
      return;                                             // don't do anything else
    }
    this .v1 = this .fillOutRow(vacRow[0], vacRow[1], this .v1, 1, dBC)
    this .v1 += (vacRow[1]['vacLength'] )                       // increment to end of second tA  tA[1]                  
    if (!vacRow[2]){                                      // if this is the LAST tA / there is NO THIRD tA
      this .makeTillEndDays(this .v1,2);                        // fill out the rest of the days
      return;
    }
    // if there is a THIRD tA  
    this .v1 = this .fillOutRow(vacRow[1], vacRow[2], this .v1, 2, dBC)
    this .v1 += vacRow[2]['vacLength']
    if (!vacRow[3]){
      this .makeTillEndDays(this .v1,3);
      vacRow[2][10] = this .makeTillEndBoxes(vacRow[2])
      return;
    }
      // if there is a FOURTH tA
    this .v1 = this .fillOutRow(vacRow[2], vacRow[3], this .v1, 3, dBC)
      this .v1 += vacRow[3]['vacLength']
      if (!vacRow[4]){
        this .makeTillEndDays(this .v1,4);
        vacRow[3][10] = this .makeTillEndBoxes(vacRow[3])
        return;
      }  
}                                           // end of loop to fill out calendar row to tA. 

public advanceMonth(n){
  this. monthInc += n;
  this. vacData = null;                                         // don't draw until new data
  this .dayNum = 0
  this. getVacURL = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDtAs.php?adv='+ this.monthInc;
  this .setCalDates();
  this. getTheData();
}
/**
 * Fills out row with dayNumbers matching the dayNumber of calendar top row, so can detect TODAY, Used by makeDaysOfRow
 * @param tA0 The EARLIER of the pair of tAs
 * @param tA1 the LATER of the pair of tAs
 * @param v1  The current day/index
 * @param n   The index of the row, e.g. the 3rd row in the calendar
 */
private fillOutRow(tA0, tA1, v1, n, dayBefore){
  let d1 = this. daysBetweenA(tA0['endDate'], tA1['startDate']) -1
  //let dayBefore = this. daysBeforeCalcStart(tA0)
  for (let k=0; k < d1; k++){                           // loop and push required dayNums
    this .v1++;                                                                           
    if (!this .dayArray[n]){
      this .dayArray[n] = Array();
      this .dayArray[n][0] = this .v1 - dayBefore;
    }
    else
      this .dayArray[n].push(this .v1 - dayBefore) ;                         // into the dataStruct
  }
  return this .v1;
}
/**
 *  Fills in the days from the last day of the tA till the end of the month
 *  Used when this is the last tA of a row
 * @param v1   this index/day of the last day of the tA
 * @param n    the index of the dayArray which is being filled in
 */
private makeTillEndDays(v1, n ){
  let tillEnd = this .numDaysOnCal- v1;
  if (this .monthInc > 0){
    console.log("164 %o", this .numDaysOnCal)
  }
  for (let k=0; k < tillEnd; k++){
    v1++
    if (!this .dayArray[n]){
      this .dayArray[n] = Array()
      this. dayArray[n][0] = v1;
    }
    else
      this .dayArray[n].push(v1);
  }   
}
/**
 * Creates an array used by *ngFor to create just enuff boxes for the CalendarRow
 * @param vac 
 * @returns 
 */
private makeTillEndBoxes(vac){
  const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
  let endDate = new Date(vac['endDate'])
  var calEndDate = new Date( this. calDates[this. calDates.length-1])
  var diff =Math.round( (calEndDate.valueOf() - endDate.valueOf())/oneDay);
  let arr = Array()
  for (let i=0; i < diff; i++)
    arr[i] = i;
  return arr;
}
/**
 * Loads parameters to use when the Save Edits button is clicked
 * @param type 
 * @param ev 
 */
private  editDate(type: string, ev: MatDatepickerInputEvent<Date>) {
    let dateString = this.datePipe.transform(ev.value, 'yyyy-MM-dd')
    if (type.indexOf("start") >= 0){
      this .tAparams.startDate = dateString;
    }
    if (type.indexOf("end") >= 0){
      this .tAparams.endDate = dateString;
    }
}
private deleteTa(){
  this .tAparams.reasonIdx = 99;
  this .saveEdits();
}
private saveEdits() {
  var jData = JSON.stringify(this .tAparams)                        // form the data to pass to php script
  var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/editAngVac.php';  // set endPoint
    this .http.post(url, jData).subscribe(res =>{                     // do the http.post
      this .getTheData();                                           // refresh the data to show the edits. 
  })
  this .showEdit = false;                                           // turn of editControl box. 
}
private editReasonIdx(ev){
  console.log("66 %o", ev)
  
}

/**
 * Calculate the number of days from firstDayOnCalendar to start of tA
 * @param vac 
 * @returns dBC
 */
public daysBeforeCalcStart(vac){
  let theStartDate = new Date(vac['startDate'])
  var diff = this .calDates[0].valueOf() - theStartDate.valueOf() ;
  diff = Math.ceil(diff / (1000 * 3600 * 24));
  if (diff >  0){
    return  diff -1 ;
  }
  return 0;
}
/**
 * Used when user clicks on her tA, to show the edit controls. 
 * @param vacEdit 
 */
 private showEditFunc(vacEdit){
   this .startDateConvent = this.datePipe.transform(vacEdit.startDate, 'MM-d-yyyy')
   this .endDateConvent = this.datePipe.transform(vacEdit.endDate, 'MM-d-yyyy')
   this .tAparams ={} as tAparams;
   this .tAparams.vidx  = vacEdit.vidx;
   this .vacEdit = vacEdit; 
   this. showEdit = true;
 } 

 /**
  * Determines if a day on Calendar Top is a Weekend or Today
  * @param d 
  * @returns weekend OR todayCell
  */ 
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
  /**
   * Determines if day on calendar is Today
   * @param n day on the calendar
   * @returns 
   */
  getClass(n){
    if (n == this .dayOfMonth && this. monthInc == 0)
      return 'todayCell'
  }

 /**
  * Go to new Row on Calendar
  */
  zeroDayNum(){                                         // reset the dayNum for each row of Cal
    this. dayNum = 0;
  }




    getUsers(){
    var url = 'https://ion.mgh.harvard.edu/cgi-bin/imrtqa/getUsers.php';
    return this .http.get(url)
  }
//  setUsers(res){
//    this. users = res;
//  }
  setData(res ) {

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
counterBetween(n, m){
  var ar = Array();  
  for (var i = +n; i <= +m; i++){
    ar[i] = i;
  }
console.log("336 %o", ar)    
  return ar;  
}  
counterE(n){                                            // used for looper in Calendar
    var ar = Array();
    n = n -1;

    for (var i=0; i < n; i++ ){
      ar[i] = i;

    }
 
    return ar;
}

  setCalDates(){
    const monthNames = ["January", "February", "March", "April", "May", "June",
             "July", "August", "September", "October", "November", "December"
              ];
      this .calParams = {} as calParams
      var date = new Date();
     // date = new Date(date.setMonth(date.getMonth()+ this .monthInc));
      var monthToAdd = 2 + this. monthInc
      var monthToAddStart = this. monthInc
      var daysInMonth0 = date.getDate();
      var firstDay = new Date(date.getFullYear(), date.getMonth() + monthToAddStart, 1);
      this .calParams.firstMonthName = firstDay.toLocaleString('default', { month: 'long' });
      this .calParams.daysInFirstMonth = new Date(firstDay.getFullYear(), firstDay.getMonth() + 1, 0).getDate();
      var lastDay = new Date(date.getFullYear(), date.getMonth() + monthToAdd, 0);
      this .calParams.lastDateOnCal = lastDay
      this .calParams.daysInSecondMonth = new Date(lastDay.getFullYear(), lastDay.getMonth() + 1, 0).getDate();
      this .calParams.secondMonthName = lastDay.toLocaleString('default', { month: 'long' });
      this. calDates = Array();
      this .calParams.firstMonthName = monthNames[firstDay.getMonth()]
      var i = 0;
// if (this. monthInc > 0)
 {
   console.log("348 %o --- %o", firstDay, this .calParams)
 }     
      do {
        var cDay = new Date(firstDay.valueOf());
        this. calDates[i++] = cDay;
        firstDay.setDate(firstDay.getDate() + 1);
      }
      while (firstDay <= lastDay)
  //    this .numDaysOnCal = 61; 
    }

  daysTillEnd(val){
      const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
      if (!val)
          return;
      var endDate = new Date(val['endDate'])
/* if (this. monthInc > 0) {    
    console.log("359 %o", val)
 }*/   
      endDate = new Date(endDate.getTime() + endDate.getTimezoneOffset() * 60000)
 //     endDate.toLocaleString('en-US', { timeZone: 'America/New_York' })
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
//console.log("420 %o --- %o --- %o", d1, d2, diff)    
    return diff ;
  } 

}
