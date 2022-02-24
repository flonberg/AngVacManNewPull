import { AppComponent } from './../app.component';
import { HttpClient } from '@angular/common/http';
import { DatePipe, NumberSymbol } from '@angular/common';
import { analyzeAndValidateNgModules } from '@angular/compiler';
import { Component, EventEmitter, OnInit, Output } from '@angular/core';
import { MatDatepickerInputEvent } from '@angular/material/datepicker';
import { throwError } from 'rxjs';
import { connectableObservableDescriptor } from 'rxjs/internal/observable/ConnectableObservable';
import { ActivatedRoute } from '@angular/router';
interface tAparams {
  startDate? : string,
  endDate?: string,
  reasonIdx?: number,
  reason?: number,
  note?: string,
  coverageA: 0,
  WTMnote?: string,
  WTMdate?: string,
  vidx: number;
  CovAccepted: number;
  WTMcoverer: string;
  WTMchange:number;
  WTM_self:Number;
  userid: number;
  WTM_Change_Needed: number;
  service: number;
}
interface CovParams {
  accepted: boolean,
  WTMdate?: string,
  WTMnote?: string,
  vidx: number
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
  userid: string;
  vidxToSee: number;                               // used by AcceptCoverage control
  //vidxToSeeData: object;
  isLoggedInUserCoverer: boolean = false;
  monthInc:number = 0;
  getVacURL = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDtAs.php?adv='+ this.monthInc;
  vacData: any;
  coverers: any;                                    // the coveres are the MDs in the same SERVICE as goAwayer
  vacEdit: any;                                     // data for the EditVac boxes 
  showEdit: boolean = false;
  showAcceptance: boolean = false; 
  dayArray = [[]]                                  // make the boxes BEFORE and BETWEEN and TILL_END of timeAways 
  calDates: Date[] = Array();                       // for filling out the calendar and making the TODAY stiled boxes
  reasonArr = ['null', 'Personal Vacation','Meeting','Health', 'Other']
  dayOfMonth: number;
  showReadOnly:boolean = false
  tAparams: tAparams;                               // used in Edit box and AcceptCoverage box
  toSeeParams: any;                                 // the params used in the AcceptCoverage box
  reasonIdx: string;                                // reason[reasonIdx]  and reasonIdx =99 for delete
  startDateConvent: string;
  endDateConvent: string;
  WTMDateConvent: string;
  covAccepted: boolean;
  WTMnote: string;
  v1: number;
  numDaysOnCal: number;
  calParams: calParams;                             // e.g daysInSecondMonth, firstMonthName
  dayNum: number = 1;
  vidxToEdit: number = 0;                           // for debugging
  acknowlegeEdits: string = '-';
  serviceMDs: any;
  MDservice: {};
  CovParams: CovParams;
  CovererView: boolean = false;
  T_WTM_self: number = 0;
  sDD: string = '';
  stDt: string = '';
  changesSavedShow = false;
  loggedInUserKey = 0;
  isUserAnMD = false;



  constructor(private http: HttpClient, private datePipe: DatePipe , private activatedRoute: ActivatedRoute) {
    this. activatedRoute.queryParams.subscribe(params =>{
      this .userid = params['userid']
      this .vidxToSee = params['vidxToSee']          // used by Coverer to Accept Coverage and Select WTM date
      if (params['vidxToSee']){
        this .getTheVidxToSee();
        this .showAcceptance = true;
        this .CovererView = true; 
      }
   //   this .getVacURL += '&userid=suit'
      this .getTheData();
      this .getServiceMDs(this .userid)
      this. getMDService();

    })
   }

  ngOnInit(): void {
    this .dayOfMonth = new Date().getDate();
    this .reasonIdx = "1";
    this .numDaysOnCal = 61;
    let tstVar = 1;                             // for new push to remote. 
  
   // this .firstTest = 0;
    this .vacData = Array();
    this. setCalDates();
    this .CovParams = {
      accepted: false,
      vidx: 0
    }
    this. showError = 0; 
    
    this. tAparams = {
        startDate :'',
        endDate: '',
        reasonIdx: -1,
        note: '',
        userid: 0,
        coverageA: 0,
        WTMdate:'',
        WTMchange: 0,
        WTMcoverer:'',
        WTM_self: -1,
        vidx: 0,
        CovAccepted: 0,
        WTM_Change_Needed: 0,
        service: 0
      }
      this. activatedRoute.queryParams.subscribe(params =>{
        this .userid = params['userid']
        this .tAparams.userid = params['userid']
        console.log("125  %o", this .userid)
     
        if (this .userid){
          let url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getLoggedInUserKey.php?userid='+ this .userid
          this .http.get(url).subscribe(res =>{
            let returnedObj = res;
            this .loggedInUserKey = returnedObj['LoggedInUserKey'];
          })
        }
        
      })
    }    
    unsorted() { }                                                      // user by alphabetization of the data by service 
    
  private getTheVidxToSee(){
    let url  = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getVidxToSee.php?vidxToSee='+ this.vidxToSee + '&userid=' + this .userid;
    this .http.get(url).subscribe(res =>{
        this .toSeeParams = res;
        console.log("818181 %o", this .toSeeParams)
        if (+this .toSeeParams['loggedInUserKey'] ==this .toSeeParams['coverageA']){
          this .isLoggedInUserCoverer = true;
        }
        if (this. toSeeParams['WTMdate']  && this .toSeeParams['WTMdate'].length > 4 )
          this .WTMDateConvent = this.datePipe.transform(this. toSeeParams['WTMdate'].date, 'MM-d-yyyy')
        if (this .toSeeParams['CovAccepted'] == 1)
          this .covAccepted = true;  
        this. WTMnote = this .toSeeParams['WTMnote']  
    })

  }
  /**
   * Get tA data from 242.  The URL has GET params det'ing the monthInc, which det's the 2-month data acquisition interval
   */  
  private getTheData(){
    console.log("68 url is %o", this .getVacURL)
    this .http.get(this .getVacURL).subscribe(res =>{
      this .vacData = res['tAs'];
      this .sortByService();
      this .coverers = res['coverers']
        console.log("165 vacData is %o", this. vacData)
        console.log("166 coverers is %o", this. coverers)
      for (const tRow in this. vacData){ 
        this.makeDaysOfRow(this .vacData[tRow])               // fill out the row of boxes for the Calenday
        this .vacData[tRow][9] = (this .dayArray);            // dayArray is array of dayNumbers used to det the TODAY box      
    //    this .vacData[tRow][10] = (this .dayArray);            // dayArray is array of dayNumbers used to det the TODAY box      
      }  
    })
  }  
  private sortByService(){
    let byServArr = Array();
    for(let i=0; i< this .vacData.length; i++){
    //  console.log(this .vacData[i][0]['service']  ); //use i instead of 0
      let serv = this .vacData[i][0]['service'] ;
      if (!byServArr[serv]){
        byServArr[serv] = Array();
        byServArr[serv][0] = this .vacData[i]
      }     
      else {
        let currInd = byServArr[serv].length;
        byServArr[serv][currInd+1] = this .vacData[i]
      }  
    }
   console.log("201 byServArr is %o", byServArr)
 /*   for(let i=0; i< byServArr.length; i++){
      if (byServArr[i]){
      let len2 = byServArr[i].length ;
      if (len2 < 2)
        continue;
      console.log("208 len2 is %o", len2);
      if (byServArr[i] && byServArr[i].length > 1){
        //   console.log("207 test if %o", byServArr[i])
        //   for (var item of byServArr[i]){
        //       console.log("210 item is %o", item )
        //     }
            }
          }
        }
        */
    }
  private getServiceMDs(userid){
    let url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDsByService.php?userid='+ userid
    this .http.get(url).subscribe(res =>{
      this. serviceMDs = res;
      for(let entry of this .serviceMDs){
        if (this .userid == entry.UserID)
          this .isUserAnMD = true;
              }    
console.log("198 serviceMDs is %o", this. serviceMDs)
          })
  }

 private getMDService(){
    let url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDService.php'
    this .http.get(url).subscribe(res =>{
      this. MDservice = res;
          })
  }


   /**
  * Used by enterTa to signal a new tA has been added and we need to reload the data. 
  * @param ev 
  */
 public refreshData(date: any){                                     // <app-enterta (onDatePicked) = "refreshData($event)"  ></app-enterta>
  this .showEdit = false;
  let tst = this .howManyMonthForward(date) 
  console.log("9999 %o", tst)
  this .monthInc += this .howManyMonthForward(date) 
  this .advanceMonth( 0)
}               
private howManyMonthForward(date){
  let n = 0
  let testDate = this .calParams.lastDateOnCal                            // create a date to modify
  if (date < this .calParams.lastDateOnCal)                               // if the new tA startDate is in present calendar range
    return n;                                                             // return 0
  do {
    n++;                                                                  // increment the parameter
    testDate = new Date(testDate.getFullYear(), testDate.getMonth() + n, 0)      // last date of next month 
  } while  (date > testDate && n < 10) ;                                  // if new tA StartDate is in the month AFTER last month on calendar
  return n                                                                // return index which is the number of months cal has to go forward to includ the tA 
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
      this .makeTillEndDays(this .v1,1);                        // fill out the rest of the dayxNum
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
  let tillEnd = this .numDaysOnCal- v1 ;
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
  for (let i=0; i <= diff; i++)
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
    if (type.indexOf("start") >= 0)
      this .tAparams.startDate = dateString;
    if (type.indexOf("end") >= 0)
      this .tAparams.endDate = dateString;
    if (type.indexOf("WTM") >= 0)
      this .tAparams.WTMdate = dateString;  
    this .changesSavedShow = false;
}

private deleteTa(ev){
  this .tAparams.reasonIdx = 99;
  this .tAparams. userid = this. vacEdit. userid;
  this .stDt = ""; 
  this .saveEdits(ev);
  location.reload();

}
private saveEdits(ev, detail?) {
  this .tAparams.vidx = +this .vidxToEdit;
  var jData = JSON.stringify(this .tAparams)                      // the default edit params
  var emailParam = 0;                                             // determines IF and WHICH email 2b sent
  if (detail == 'CovAccept'){
    this. acknowlegeEdits = 'Edits Saved'
    this. CovParams.vidx = this .vidxToSee                   
    jData = JSON.stringify(this. CovParams)                       // params for Coverer/Acceptance. 
  }
  if (detail){  
    if (detail =='tAchanged')
      emailParam = 1;
    if (detail.includes('Accept'))                                      // Coverer accepted
      emailParam = 2;   
  }                                           // signal for Final Email to Nurses and Admins  
  console.log("341 tAparams is  %o  detail is %o  jData is %o ", this .tAparams, detail, jData)
  console.log("367 detail is %o emalparam is %o", detail, emailParam)
                      // form the data to pass to php script
  var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/editAngVac.php?email='+emailParam;  // set endPoint
    this .http.post(url, jData).subscribe(res =>{                     // do the http.post
      this .getTheData();                                           // refresh the data to show the edits. 
  })
  this .changesSavedShow = true;
  this .showAcceptance = false; 
 // this .ngOnInit();

}
private editCovParams(param, value){
  console.log('305 %o --- %o', param, value);
  if (param == 'CovAccepted')
      this .CovParams.accepted = value;
  if (param == 'WTMnote')
      this .CovParams.WTMnote = value.target.value;
  if (param == 'WTMdate'){
    this .CovParams.WTMdate = this.datePipe.transform(value.value, 'yyyy-MM-dd')
    console.log( 'this.CovParams has %o', this .CovParams);
  }
}
private isWTM_self(){
  if (this .vacEdit.WTM_self == 1)
    return true;
}
private editTaParams(name, value){  
  if (!this. tAparams)
      this .tAparams ={} as tAparams;
console.log("376 name is %o", name)
  switch (name){
    case 'WTMdate':{
      let dateString = this.datePipe.transform(value.value, 'yyyy-MM-dd')
      this .tAparams.WTMdate = dateString;
      break;
    }  
    case 'reasonIdx':{
      this .tAparams.reasonIdx = value.value;  
      break;
    }         
    case 'WTM_Self':{
      console.log("354 WTM_self %o", value)
      this .vacEdit.WTM_self = 1;
      this .tAparams.WTM_self = 1;
      break;
    }    
    case 'NOT_WTM_Self':{
      this .vacEdit.WTM_self = 0;
      this .tAparams.WTM_self = 0;
      break;
    }
    case 'coverageA':{
      console.log("361 coverageA %o", value.value)
      this .tAparams.coverageA = value.value;
      break;
    }
    case 'covAccepted':{
      this .tAparams.CovAccepted = value;
      break;
    } 
    case 'note':{
      this .tAparams.note = value;   
      break;
    } 
    case 'WTMnote':{
      this .tAparams.WTMnote = value.target.value;   
      break;
    } 
    default: { 
      console.log("Invalid choice"); 
      break;              
   }

  }
  this .changesSavedShow = false;
  console.log("419  in editTaParams tAparams is %o name is %o", this. tAparams, name)    
  this .tAparams.vidx = this .vidxToSee  


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
selectedOption:string

/**
 * Used when user clicks on her tA, to show the edit controls. 
 * @param vacEdit 
 */
 private showEditFunc(vacEdit){
  this .tAparams ={} as tAparams;
   this .selectedOption = "1";
  let isUserGoAwayer = this. userid.includes(vacEdit['userid'])
  console.log("276 vacEdit %o  --- %o --- %o", vacEdit, this. userid, isUserGoAwayer) 
   this .startDateConvent = this.datePipe.transform(vacEdit.startDate, 'MMM-d-yyyy')
   this .endDateConvent = this.datePipe.transform(vacEdit.endDate, 'MMM-d-yyyy')
   if (vacEdit.WTMdate.includes('1900')) 
    this .WTMDateConvent = '';  
    else 
      this .WTMDateConvent = this.datePipe.transform(vacEdit.WTMdate, 'MMM-d-yyyy')
  console.log("458 WTMDateConvent is %o", this.WTMDateConvent)    
  this .tAparams.vidx  = vacEdit.vidx;
   this .vidxToEdit = vacEdit.vidx;                   // for debugging
  // this .tAparams.note  = vacEdit.note;
   this .selectedOption = String(vacEdit.reasonIdx)
   this .vacEdit = vacEdit; 
   this. showEdit =  this. userid.includes(vacEdit['userid']) 
   this. showReadOnly =  !this. userid.includes(vacEdit['userid']) 
 } 
private validDate(dateString){
  if (dateString.includes('1900'))
    return '';
  else  
    return dateString;
}
 /**
  * Determines if a day on Calendar Top is a Weekend or Today
  * @param d 
  * @returns weekend OR todayCell
  */ 
 getDateClass(d: Date){
    let today = new Date()

 

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
    n--;                                                // accomodate Service column
    if (n == this .dayOfMonth && this. monthInc == 0)
      return 'todayCell'
  }

  getNameClass(d){
  //  console.log("565 in getNameClass %o", d);
    if (d['overlap'] == 1 && d['class'] == 'orange')
      return "orangeOverlap";
    if (d['overlap'] == 1 && d['class'] == 'green')
      return "greenOverlap";  
    return d['class'];
  }

  getAcceptanceClass(n){
    console.log("501 n is %o", n);
    if (n == 1)
      return 'green'
    return "orange";
  }

 /**
  * Go to new Row on Calendar
  */
  zeroDayNum(){                                         // reset the dayNum for each row of Cal
    this. dayNum = 0;
  }
  showService(n, service){
    if (n == 0 && this. MDservice){
      return this .MDservice[service]['service'];
     // return service
    }
    else  
      return ''; 
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
     return diff +1;
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
  startDateEntered: Date;
  dateRangeChange(dateRangeStart: HTMLInputElement, dateRangeEnd: HTMLInputElement) {
    var tDate = new Date(dateRangeStart.value)                              // save for editing
    this .startDateEntered = tDate;
    this .monthInc = this.whatMonthIsStartDateIn(tDate)
    if (  dateRangeEnd.value && dateRangeStart.value ){
   //  var eDate = new Date(dateRangeEnd.value)
        this. tAparams.startDate = this .datePipe.transform(new Date(dateRangeStart.value), 'yyyy-MM-dd')   
        this. tAparams.endDate = this .datePipe.transform(new Date(dateRangeEnd.value), 'yyyy-MM-dd')   
        if (this .showError == 2)
          this .showError = 0;
      }

 }
 whatMonthIsStartDateIn(startDate){
  let thisMonth = new Date();
  var lastDate = new Date(thisMonth.getFullYear(), thisMonth.getMonth() + 2, 0);
  if (startDate < lastDate)
   return 0;
 return 1;
}
showError: number;
errorTxt: string;
checkTAparams(){
  this .errorTxt = ""; 
  console.log("647 checkparaism has %o", this. tAparams)
  if (!this .tAparams){
    this. errorTxt = "Please enter all parameters";
    this .showError = 1;
    return false;
  }

  if (this. tAparams.startDate.length < 3 || this. tAparams.endDate.length < 3){
    this. errorTxt = "Please enter Start and End Date";
    this .showError = 2;
    return false;
  }
  if (this. tAparams.reasonIdx < 0){
    this. errorTxt = "Please enter a Reason";
    this .showError = 3;
    return false;
  }
  if (this. tAparams.coverageA < 1){
    this. errorTxt = "Please enter a Coverer";
    this .showError = 4;
    return false;
  } 
  if (this .tAparams. WTMchange == 1){
    if (this .tAparams.WTM_self < 0){
      this. errorTxt = "Please select Self or Covering MD";
      this .showError = 5;
      return false
    }
    if (this .tAparams.WTM_self == 1){
      if (this .tAparams.WTMdate.length < 3){
        this. errorTxt = "Please select a WTM Date";
        this .showError = 6;
        return false
      }
    }
  }
  this .showError = 0;
  return true
 }
 reasonSelect(ev){
  if (this .tAparams){
    this .tAparams.reasonIdx= ev.value;
    if (this .showError == 3)
      this .showError  = 0;
  }
}
covererSelect(ev){
 this .tAparams.coverageA = ev.value.UserKey
 if (this .showError == 4)
  this .showError  = 0;
}
noteChange(name, ev){
if (this .tAparams)
  this .tAparams.note= ev.target.value;
  console.log("note is %o", this.tAparams)
}
WTMnoteChange( ev){
  if (this .tAparams)
    this .tAparams.WTMnote= ev.target.value;
    console.log("tAparams is %o", this.tAparams)
  }
WTMparam(ev, pName){
  console.log("101 %o --- %o ", ev, pName)
  if (pName == 'WTMdateChange')
    this .tAparams.WTMchange = ev.checked ? 1 : 0
  if (pName == 'WTM_Self'){
    this .tAparams.WTM_self = 1
    
    if (this .showError == 5)
      this .showError  = 0;
  }
  if (pName == 'WTM_CoveringMD'){
    this .tAparams.WTM_self = 0
    if (this .showError == 5)
      this .showError  = 0;
  }
  if (pName == 'WTMdate')
   this. tAparams.WTMdate = this .datePipe.transform(new Date(ev.value), 'yyyy-MM-dd')  
}
postRes: any;
overlap: boolean
faultMessage; string;
submitTA(){                                                                  // need to put in full error checking. 
  this .faultMessage = "t";
  if (this .checkTAparams()){
      var jData = JSON.stringify(this .tAparams)
      var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/enterAngVac.php';
      this .http.post(url, jData).subscribe(ret=>{
          this .postRes = (ret)                                         // php returns 0 for overlap and 1 for clean
            this .overlap = this. postRes['result'] == 0 ? true : false;    // turn on Warning message. 
            {
              let faultArray = this. safeJSONparse(this. postRes);
              console.log("697 postRes %o", faultArray)
              if (faultArray && faultArray.test == 'CoverageA'){
                  this .errorTxt = 'Please re-enter Coverage';
                  this .showError = 3;
              }
            }
            this .getTheData();  
            }
        )
      this .sDD = '';
      }
   else {
     console.log("756 cheTAparams is return false");
   }   
 }
safeJSONparse(jsonString) {
  var valid = false;
  var json = jsonString;
  try {
      json = JSON.parse(jsonString);
      valid = true;
  } catch (e) {}
  return (json);
}
showConventDate(date){
  return this.datePipe.transform(date, 'MMM-d-yyyy')
}
}
