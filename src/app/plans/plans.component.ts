import { HttpClient } from '@angular/common/http';
import { analyzeAndValidateNgModules } from '@angular/compiler';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-plans',
  templateUrl: './plans.component.html',
  styleUrls: ['./plans.component.css']
})
export class PlansComponent implements OnInit {
  panelOpenState: boolean;
  vacData: any;
  users: any;
  calDates: Date[];
  constructor(private http: HttpClient) { 
    
  }

  ngOnInit(): void {
    this .vacData = Array();
    this.getVacs().subscribe(res =>{
      console.log("res is %o", res)
      this.getUsers().subscribe(rusers=>{
        for (const vr in res){
     //     console.log("userid is %o", vr)
     //     console.log("vr is %o", res[vr])
          if (rusers[vr]){
        //  console.log("rusers is %o", rusers[vr]['LastName'])
            this. vacData[rusers[vr]['LastName']] = res[vr]
          }
          
        }
        console.log("this 99999  is %o", this. vacData)
      })
     
    //  this.setData(res);
    })
    this. setCalDates();
  }
  getVacs(){
    var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getVacs.php';
    return this .http.get(url)
  }
  getUsers(){
    var url = 'https://ion.mgh.harvard.edu/cgi-bin/imrtqa/getUsers.php';
    return this .http.get(url)
  }
  setUsers(res){
    this. users = res;
   // console.log("users is %o", this. users)
  }
  setData(res ) {
    this.getUsers().subscribe(res =>{
      this.setUsers(res);
    })
      console.log("111")
      for(const vr in res){
        var uKey = res[vr][0]['userid']
        if (this .users)
           console.log( "vr is %o", this. users[uKey]) 
     //   console.log("lastName is %o", this. users[res[vr][0]['userid'] ])
      }
      this.vacData = res;
     // console.log(this.vacData)
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
      var firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
      var lastDay = new Date(date.getFullYear(), date.getMonth() + 2, 0);
      this. calDates = Array();
      console.log("lsstDay is %o", lastDay);
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
    myFunction(val){
      let d2 = 0;
      let ed = "";
      const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
      console.log(" val is %o", val['endDate'])
      var endDate = new Date(val['endDate'])
      var calEndDate = new Date( this. calDates[this. calDates.length-1])
      console.log("calDates is %o", this. calDates[this. calDates.length-1])
      var diff =Math.round( (calEndDate.valueOf() - endDate.valueOf())/oneDay);
      console.log("diff is %o", diff)

   
     return diff;
    }
}
