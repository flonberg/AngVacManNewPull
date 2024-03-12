import { AppComponent } from './../app.component';
import { HttpClient } from '@angular/common/http';
import { DatePipe, NumberSymbol } from '@angular/common';
import { analyzeAndValidateNgModules, templateSourceUrl } from '@angular/compiler';
import { Component, EventEmitter, OnInit, Output } from '@angular/core';
import { MatDatepickerInputEvent } from '@angular/material/datepicker';
import { throwError } from 'rxjs';
import { connectableObservableDescriptor } from 'rxjs/internal/observable/ConnectableObservable';
import { ActivatedRoute } from '@angular/router';
import { isDefined } from '@angular/compiler/src/util';

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
  WTMcovererUserKey: number;
  WTMchange:number;
  WTM_self:Number;
  userid: number;
  WTM_Change_Needed: number;
  service: number;
  CompoundCoverage: number;
  CompoundCoverers: number[]
  CoverDays: CoverDay[]
}
interface CovParams {
  accepted: boolean,
  WTMdate?: string,
  WTMnote?: string,
  vidx: number,
  toSeeParams?: any
}
interface calParams {
  firstMonthName: string,
  secondMonthName: string,
  daysInFirstMonth:number,
  daysInSecondMonth: number,
  lastDateOnCal: Date,
}
interface CompCovParams {
  idx: number,
  vidx: number,
  CovererUserKey: number,
  date: string,
  deleted: number,
  accepter: number
}

@Component({
  selector: 'app-plans',
  templateUrl: './plans.component.html',
  styleUrls: ['./plans.component.css'],
 
})

export class PlansComponent implements OnInit {
  userid: string;
  vidxToSee: number;                               // used by AcceptCoverage control
  CompCovParamsArray: CompCovParams[]
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
  calParams: calParams;                               // e.g daysInSecondMonth, firstMonthName
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
  wkDev = "dev";
  queryParams:{}
  showAcceptor: boolean = false;
  responseSaved: boolean = false;
  now: Date
  compCovLoops: number[] = [0,1,2]
  realEmails: boolean = false
  constructor(private http: HttpClient, private datePipe: DatePipe , private activatedRoute: ActivatedRoute) {
    this.now = new Date()
    if ( this .checkWorkingDir() == 'prod')                    
      this .wkDev = 'prod';
    this .getVacURL = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this.wkDev+'/getMDtAs.php?adv='+ this.monthInc;  
  console.log("9898 url is %o", this.getVacURL)  
    this. activatedRoute.queryParams.subscribe(params =>{
      this .queryParams = params
      if (this.queryParams['acceptor'] == '1'){
        this .showAcceptance = true
        this .showAcceptor = true
        this .showReadOnly = false
        this .showEdit = false
      }
      this .userid = params['userid']
  console.log("95959 userid is %o", this.queryParams)    
      this .vidxToSee = params['vidxToSee']          // used by Coverer to Accept Coverage and Select WTM date
      if (params['vidxToSee']){
        this .getTheVidxToSee();
        this .getServiceMDs(this .userid)
        this .CovererView = true; 
      }
      this .getTheData();
      this .getServiceMDs(this .userid)
      this. getMDService();
      this .getMessage();
    })
   }

  ngOnInit(): void {
    var test = 3.14                                       // test for GIT
   // this .wkDev = this . checkWorkingDir()                // get the Working Directory to switch dev/proc
    console.log("110110 wkDev is %o", this .wkDev)
    this .dayOfMonth = new Date().getDate();
    this .reasonIdx = "1";
    this .numDaysOnCal = 61;
    let tstVar = 1;                             // for new push to remote. 
  
   // this .firstTest = 0;
    this .vacData = Array();
    this. setCalDates();
    this .CovParams = {
      accepted: false,
      vidx: 0,
    
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
        WTMcovererUserKey:0,
        WTM_self: -1,
        vidx: 0,
        CovAccepted: 0,
        WTM_Change_Needed: 0,
        service: 0,
        CompoundCoverage: 0,
        CompoundCoverers: [],
        CoverDays:[]
      }
      this. activatedRoute.queryParams.subscribe(params =>{
        this .queryParams = params
        this .userid = params['userid']
        this .tAparams.userid = params['userid']
        console.log("125 queryParams is  %o", this.queryParams)
        if (this .userid){
          let url = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this.wkDev+'/getLoggedInUserKey.php?userid='+ this .userid
          this .http.get(url).subscribe(res =>{
            let returnedObj = res;
            this .loggedInUserKey = returnedObj['LoggedInUserKey'];    
          })
        }
      })
    }   
    checkWorkingDir(){
      var loc = window.location.pathname;
      console.log("116116  loc is %o ", loc)
      let test = loc.length
      if (loc.length < 5)
        return 'dev'
      else
        return 'prod'
    }
    unsorted() { }                                                      // user by alphabetization of the data by service 
  loggedInUserCoverage: number[] =  [] 
  private getTheVidxToSee(){
    let url  = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this. wkDev+'/getVidxToSee.php?vidxToSee='+ this.vidxToSee + '&userid=' + this .userid;
    this .http.get(url).subscribe(res =>{
        this .toSeeParams = res;
        console.log("195195 toSeeParams is %o", this .toSeeParams)
    for (var key in this .toSeeParams.Coverage) {
      if (this .toSeeParams['loggedInUserKey'] == this .toSeeParams.Coverage[key]['CovererUserKey'])
        this .loggedInUserCoverage.push( this .toSeeParams.Coverage[key])
    }

    this .toSeeParams['WTM_Coverer_LastName']= this .toSeeParams.Coverage[key]['CovererUserKey']
    console.log("213213 %o", this.toSeeParams['WTM_CovererUserKey'])
    for (let key in this.serviceMDs){
      if (this.serviceMDs[key]['UserKey'] == this.toSeeParams['WTM_CovererUserKey']){
        console.log("215215  %o  --- %o  ", this.serviceMDs[key]['UserKey'], this.toSeeParams['WTM_CovererUserKey'] )
        console.log("218218 %o",this.serviceMDs[key]['LastName']  )
      }
    }
    this .goAwayerLastName2 = this.toSeeParams.goAwayerLastName
    if (+this .toSeeParams['loggedInUserKey'] == this .toSeeParams['coverageA'])
      this .isLoggedInUserCoverer = true;
    if (this. toSeeParams['WTMdate']  && this .toSeeParams['WTMdate'].length > 4 )
      this .WTMDateConvent = this.datePipe.transform(this. toSeeParams['WTMdate'].date, 'M-d-yyyy')
    if (this .toSeeParams['CovAccepted'] == 1)
      this .covAccepted = true;  
    this. WTMnote = this .toSeeParams['WTMnote']    
    this .showEditFunc(this .toSeeParams)
    })
  }
gotCompCov:boolean = false
CompCovArray:any
  /**
 * Used when user clicks on her tA, to show the edit controls. 
 * @param vacEdit 
 */
 private showEditFunc(vacEdit){
  this.CovererView = true
  this .toSeeParams = vacEdit
    console.log("190190 toSeeParams %o", this .toSeeParams)   
    let url  = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this. wkDev+'/getCompCov.php?vidx='+ vacEdit['vidx']
    this.CompCovParamsArray = []
    this .http.get(url).subscribe(res =>{
      this.CompCovArray = res
      if (Object.keys(this.CompCovArray).length > 0){
        this .gotCompCov = true
        this .makeTAdates()
   //     this .makeCompCovDates(this.CompCovArray, this.TAdates)
      }
      else
        this .gotCompCov = false

    })
    this .tAparams ={} as tAparams;

console.log("243243 TAdates if %o", this.TAdates)    
     this .selectedOption = "1";
    let isUserGoAwayer = false
    if (this.userid && this. userid.includes(vacEdit['userid']))
      isUserGoAwayer = true
    this .startDateConvent = vacEdit.startDate
    if (vacEdit.startDate.includes('1900')){
      this .startDateConvent = 'TBD';
      this .toSeeParams.WTMdate = 'TBD';
    }
    this .endDateConvent = vacEdit.endDate  
    this .WTMDateConvent = vacEdit.WTMdate
    this .tAparams.vidx  = vacEdit.vidx;
    this .vidxToEdit = vacEdit.vidx;                   // for debugging
    this .selectedOption = String(vacEdit.reasonIdx)
    this .vacEdit = vacEdit; 
    this. showReadOnly = true
    if (this.userid &&  !this. userid.includes(vacEdit['userid']) ){
      this .showReadOnly = true
      this .showEdit = false
    }
    else if (this.userid && this. userid.includes(vacEdit['userid']) ){
      this .showEdit = true
      this .showReadOnly = false
    }
    else 
      this .showReadOnly = true
    if (this .showAcceptance){
      this .showReadOnly = false
      this .showAcceptor = true
    }
   return 
   } 
   getCompCov(num: number){
      let test = Object.keys(this .CompCovArray)[num]
      return test
   }
  /**
   * Get tA data from 242.  The URL has GET params det'ing the monthInc, which det's the 2-month data acquisition interval
   */  
  CompCov: any
  gotData:boolean = false
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
        this .gotData = true  
    //    this .vacData[tRow][10] = (this .dayArray);            // dayArray is array of dayNumbers used to det the TODAY box      
      }  
    })
    let URL = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this.wkDev+'/getAllCompCov.php';
    this .http.get(URL).subscribe(res =>{
      this .CompCov = res
      console.log("297297 %o", this .CompCov)
    })
    

  }  
  private toTBD(inp:string){
    if (inp == 'Unknown')
      return 'TBD'
    return inp
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
    }
  private getServiceMDs(userid){
    var url = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this. wkDev+'/getMDsByService.php?userid='+ userid
    this .http.get(url).subscribe(res =>{
      this. serviceMDs = res;
      var TDB_MD = {'UserKey':0,'service':99,'LastName':'TBD','UserId':'TBD'}
      for(let entry of this .serviceMDs){
        if (this .userid == entry.UserID)
          this .isUserAnMD = true;
              }  
      this .serviceMDs[41] = TDB_MD
      console.log("277277 %o", this .serviceMDs)
          })
  }
  Message: Object
  private getMessage(){
    //let url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDService.php'
    let url = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this. wkDev+'/getMessage.php';
      this .http.get(url).subscribe(res =>{
        this. Message = res;
        console.log("293293 %o", this .Message)
            })           
    }


 private getMDService(){
  //let url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getMDService.php'
  let url = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this. wkDev+'/getMDService.php';
    this .http.get(url).subscribe(res =>{
      this. MDservice = res;
          })
      return               
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
   * The creates the dayArray which is used to trigger the TODAY yellow box. 
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
private  editParam(type: string, ev: any) {
    let newParam: any
    if (typeof ev ==='string')
      newParam = ev
    else if  (typeof ev.value ==='number') // returns true or false )
      newParam = ev.value
    else
      newParam = this.datePipe.transform(ev.value, 'yyyy-MM-dd')
      console.log("490490 %o", ev)
    let vidx = this.tAparams.vidx
    var url = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this. wkDev+'/EditParam.php?vidx='+vidx+'&name='+type+'&value='+newParam
    console.log('420 url is %o', url);
      this .http.get(url).subscribe(res =>{                     // do the http.post
        this .getTheData();   
        let result = res
  console.log("492492 result is %o", result)                                              // refresh the data to show the edits. 
    })
    /*
    if (type.indexOf("start") >= 0)
      this .tAparams.startDate = dateString;
    if (type.indexOf("end") >= 0)
      this .tAparams.endDate = dateString;
    if (type.indexOf("WTM") >= 0)
      this .tAparams.WTMdate = dateString;  
    this .changesSavedShow = false;
    */
}

private deleteTa(ev){
  this .tAparams.reasonIdx = 99;
  this .tAparams. userid = this. vacEdit. userid;
  this .stDt = ""; 
console.log("390 in deleteTa tAparams is %o", this .tAparams)  
  this .saveEdits(1);
//  location.reload();

}
private changeSingleParam(name,tableName, vidx, ev, goAwayerLastName){
  console.log("5538 %o  --- %o   ---- %o  ",name , vidx,  ev)
  let toEditVal : any = ''
  if (name == 'WTMdate')
      toEditVal =ev.toISOString().split('T')[0]
  else if (Number.isFinite(ev))
    toEditVal = ev
  else 
    toEditVal = ev.value
  var url = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this. wkDev+'/editSingleParam.php?name='+name+'&tableName='+tableName+'&vidx='+vidx+'&value='+toEditVal+'&goAwayerLastName='+goAwayerLastName;  // set endPoint for dev
  console.log('420 url is %o', url);
  this.responseSaved = true;
  this .http.get(url).subscribe(res =>{                     // do the http.post
    this .getTheData();                                           // refresh the data to show the edits. 
  })
}
private saveEdits(ev, detail?) {
  this .tAparams.vidx = +this .vidxToEdit;
console.log("396 in saveEdits tAparams is %o", this .tAparams)  
  var jData = JSON.stringify(this .tAparams)                      // the default edit params
  var emailParam = 0;                                             // determines IF and WHICH email 2b sent
//  if (detail == 'CovAccept')
  {
    this. acknowlegeEdits = 'Edits Saved'
    this. CovParams.vidx = this .vidxToSee  
   // this .CovParams.toSeeParams = this .toSeeParams    
    this .CovParams = this .toSeeParams    
console.log("547 covParams %o", this .CovParams)       
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
  var url = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this. wkDev+'/editAngVac.php?email='+emailParam;  // set endPoint for dev
  console.log('420 url is %o', url);
    this .http.post(url, jData).subscribe(res =>{                     // do the http.post
      this .getTheData();                                           // refresh the data to show the edits. 
      if (ev == 1)
        location.reload();
  })
  this .changesSavedShow = true;
  this .ngOnInit();
 // if (this .wkDev == 'prod')
   // location.reload(true);
   // location.reload(true);
}
private editCovParams(param, value){
  console.log('305 %o --- %o', param, value);
  if (this .toSeeParams.CompoundCoverage == 1){
    for (var index in this.toSeeParams.Coverage) {
      if (this .loggedInUserKey ==this.toSeeParams.Coverage[index]['CovererUserKey'] )      // select coverages belonging to the loggedInUserKey
        this.toSeeParams.Coverage[index]['accepted'] = 1                                    // set the to 'accepted' 
    }
  }
  {
    if (param == 'CovAccepted'){
        this .CovParams.accepted = value;
      //  this .showAcceptor = false;
    }
    if (param == 'WTMnote')
        this .CovParams.WTMnote = value.target.value;
    if (param == 'WTMdate'){
      this .CovParams.WTMdate = this.datePipe.transform(value.value, 'yyyy-MM-dd')
      console.log( 'this.CovParams has %o', this .CovParams);
      }
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
goAwayerLastName2: string = ''

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
    let allAccepted = true;
    if (d['CompoundCoverage'] == 1){
      if (this.CompCov && typeof(this .CompCov[d['vidx']]) !== 'undefined'){
        for (let elem in this .CompCov[d['vidx']]){
          if ( this .CompCov[d['vidx']][elem]['accepted'] == 0){
            allAccepted = false
          }
        }
        if (allAccepted)
          return 'green'
      }
    }
    else {
      if (d['CovAccepted'] == 1)
        return 'green'
      if (d['coverageA'] == 0)
        return 'red'
      else
        return 'orange'
    }
    

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
        this.makeTAdates()
  console.log("754754 %o", this .TAdates)      
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
  if (this. tAparams.coverageA < 0){
    this. errorTxt = "Please enter a Coverer";
    this .showError = 4;
    return false;
  } 
  if (this .tAparams. WTMchange == 1){
    if (+this .tAparams.WTM_self < 0){
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
compoundCoverageBool: boolean = false  
setCompoundCoverage(){
  if (!this .compoundCoverageBool){
    this .compoundCoverageBool = true
    this .tAparams['CompoundCoverage'] = 1
  }
  else {
    this .compoundCoverageBool = false
    this .tAparams['CompoundCoverage'] = 0 
  }
}  
covererSelect(ev, num?:number){
  if (ev.value) 
    this .tAparams.coverageA = ev.value.UserKey
  if (typeof num !== 'undefined' ){  
    this .tAparams['CompoundCoverers'][num] = ev.value.UserKey
    }
console.log("848848 %o", this.tAparams)    
  if (this .showError == 4)
    this .showError  = 0;
}
isCovSelected(index: number){
  if (isDefined(this.tAparams.CompoundCoverers) && isDefined(this.tAparams.CompoundCoverers[index]))
    return true
  else
    return false
}
TAdates:string[]
ClassTAdates:CoverDay[]
TAdatesBool: boolean[]
TAdatesFirst: number
CompoundCoverers: number[]
/**
 * Make the array of dates for Comp Cov Checkbox array
 * @param startDateInp 
 * @param endDateInp 
 */
makeTAdates(startDateInp?:string,endDateInp?: string){
  this.TAdates = []
  this .TAdatesBool = []
  let startDateString = ''
  let endDateString = ''
  if ( this.tAparams['startDate']){
    startDateString = this.tAparams['startDate'] +"T12:00:00"
    endDateString = this.tAparams['endDate'] +"T12:00:00"
  }
  else if ( this.toSeeParams['startDate']){
    startDateString = this.toSeeParams['startDate'] +"T12:00:00"
    endDateString = this.toSeeParams['endDate'] +"T12:00:00"
  }
  let startDate = new Date(startDateString)
  let endDate = new Date(endDateString)
  let theDate = new Date(startDateString)
  let j = 0
  for (let i=0; i < 20; i++){
    let test = theDate.getDay()
    if(theDate.getDay() != 6 && theDate.getDay() != 0) {
      this .TAdatesBool[j] = false
      if (theDate !== null)
        this .TAdates[j++] = this.datePipe.transform(theDate, 'MM-dd ')
    }
    theDate.setDate(theDate.getDate() + 1);
    if (theDate > endDate)
      break
  }
}
makeCompCovDates(covs: any, dates: any){
  console.log("977 %o", covs)
  console.log("977 %o", dates)
  for (let el in dates){
    console.log("980 %o", dates[el])
    for (let el2 in covs){
      console.log("982982 %o", covs[el2])
    }
  }
}
/**
 * @param index  row number of the CompCov Checkbox array 
 * @param ind     the date of this checkbox
 * @returns 
 */
isItChecked(index: any,ind:any ){                // index is the row of the Comp Cov checkboxes
   let key = Object.keys(this .CompCovArray)[index]       // get element of CompCovArray for the 'index' coverer
   for (let el in this .CompCovArray[key] ){              // go thru the dates for that coverer
    if (ind ==  this .CompCovArray[key][el]['date'])      // if find a date matching the date of the checkbox
      return true
   }
   return false
 }
makeEditTAdates(data: any){
  this .TAdates.forEach(function(value){
    for (const key in data){
      console.log("974974 %o   --- %o", key, data[key] )
    }
  })
}
/**
 * If Checked create a covDay class element and add it to tAparams covDay array, if UnCheck remove the covDay 
 * @param CovIndex          // index of the Coverer
 * @param index             // index of the toBe Covered Date
 * @param state             // Checked of Unchecked 
 */
checkOffDate(index: number,theCovDay:number, state: any){
  if (state.checked ){                       // add the covDay               
    let covDay = new CoverDay( this .tAparams['CompoundCoverers'][index], this.TAdates[theCovDay])
    this .tAparams['CoverDays'].push(covDay)
  }
  else {                                                             // remove the covDay
    let toBeRemoved: number = -1                                      // define a number
    for (let i=0; i < this .tAparams['CoverDays'].length; i++){       // go thru the covDays
      if (this .tAparams['CoverDays'][i]['date']== this.TAdates[theCovDay]){    
        toBeRemoved= i                                                // set element to be removed
        break 
      }
    }
    this .tAparams['CoverDays'].splice(toBeRemoved,1)                 // remove element
  }
 console.log("951951 %o", this.tAparams['CoverDays'])
}
showMDlist:boolean = false
editCompCovDate(index, i, ev){
  console.log("1021 index %o ----i %o --- ev %o", index, i, ev.checked)
  console.log("1022 %o", this.CompCovArray)
  let firstIdx: number = 0
  let toEditUserKey: number = 0
  let count: number = 0
  let test:number = 0
  if (ev === false){
      for (let el in this.CompCovArray){
          console.log("1023 %o", this.CompCovArray[el])
          for (let el2 in this.CompCovArray[el] ){
            console.log("1028 %o",this.CompCovArray[el][el2]['CovererUserKey'])
            test = this.CompCovArray[el][el2]['CovererUserKey']
            if (count == 0)
              firstIdx = +el2
            if (count == i)
              toEditUserKey = this.CompCovArray[el][el2]['CovererUserKey']
            count++
          }
      }
      let idxToEdit = firstIdx + i
      var url = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this. wkDev+'/editCompCov.php?idx='+idxToEdit+'&verdict='+ev.checked+'&userkey='+toEditUserKey
      console.log('420 url is %o', url);
      this .http.get(url).subscribe(res =>{                     // do the http.post
      // this .getTheData();   
        let result = res
    console.log("492492 result is %o", result)                                              // refresh the data to show the edits. 
    } )
  }
  else {
    this .showMDlist = true
  }
}

setWTMcoverer(index: number, state: any){
  this .tAparams.WTMcovererUserKey = this .tAparams['CompoundCoverers'][index]
  console.log("898898 %o", this.tAparams) 
}

WTM_NoChange_Def: boolean = true
WTMparam(ev, pName){
  console.log("101 %o --- %o ", ev, pName)
  if (pName == 'WTMdateChange'){
    this .tAparams.WTMchange = ev.checked ? 1 : 0
    if (ev.checked == 1)
      this.WTM_NoChange_Def = false
  }
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
  if (pName == 'WTMdate'){
    console.log("1089 %o", ev.value)
   this. tAparams.WTMdate = this .datePipe.transform(new Date(ev.value), 'MM/dd/yyyy')  
  }
}
postRes: any;
overlap: boolean
faultMessage; string;
submitTA(){   
console.log("949940 %o", this .tAparams)    
  this .gotData = false                                                             // need to put in full error checking. 
  this .faultMessage = "t";
  if (this .checkTAparams()){
      var jData = JSON.stringify(this .tAparams)
      var url = 'https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/'+this. wkDev+'/enterAngVac.php?debug=1';
      this .http.post(url, jData).subscribe(ret=>{
          this .postRes = (ret)                                         // php returns 0 for overlap and 1 for clean
          if (this.postRes)
            this .overlap = this. postRes['result'] == 'selfOverlap' ? true : false;    // turn on Warning message. 
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
   console.log("854854 ret from enterAngVac is %o", this.safeJSONparse(this .postRes))
   if (this .wkDev == 'prod')
    location.reload();
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
  return this.datePipe.transform(date, 'M-dd-yyyy')
}
goToStaff(){
  var url ='https://whiteboard.partners.org/esb/FLwbe/MD_VacManAngMat/MD_Staff/dist/boot3/'
  window.open(url, "_blank");
}
goToLink(){
  var url ='https://whiteboard.partners.org/esb/FLwbe/Documentation/MD_VacManUserManual.html'
  window.open(url, "_blank");
  }
goToPhysStaffAvail(){
  var url ='https://whiteboard.partners.org/esb/FLwbe/vacation/indexPHP.php?userid='+this.userid+'&vidx=0&first=vN&func=0'
  window.open(url, "_blank");
  }  
}
class CoverDay {
  CovererUserKey:number
  date: string
  checked: boolean
  constructor( CovererUserKey, date){
    this .CovererUserKey = CovererUserKey
    this .date = date
  }
}
class CompCovClass {
  MD_LastName: string
  Dates: string[]
  Accepted: boolean[]
  constructor(MDLastName:string){
    this .MD_LastName = MDLastName
  }
}
