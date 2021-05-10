import { Component } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import * as saveAs from 'file-saver';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent {
  title = 'MDModality';
  data: any;
  planData: any;
  constructor(private http: HttpClient ) {
    this.getPlans().subscribe(res =>{
      this.setData(res);
    })
   }
   /**
    *  get the plans for a single MD 
    */
 getPlans(){
   var rnd = Math.random();
  var url = 'https://whiteboard.partners.org/esb/FLwbe/proxy.php?MDKey=test&rnd='+rnd;
  return this .http.get(url)
  }
 setData(res ) {
   this.data = res;
   console.log(this.data)
 }
 getPlanData(n){
   console.log("n is %o  key is %o", n, this.data[n]);
   var url = 'https://whiteboard.partners.org/esb/FLwbe/proxy.php?MDKey=' + n;
   this .http.get(url).subscribe(res =>{
     this. setPlanData(res)
   })
 }
 setPlanData(res){
  let areas = new Array<Array<any>>();
  this.planData = res;
  console.log("planData is %o ", this.planData)

  let tBlob= new Blob([JSON.stringify(this. planData)],{type:'application/json'})
  let tst = "one, two, ,three";

//    saveAs(blob, ' world.csv')
  let tStr = "";
  console.log("ddddd")
  console.log(Object.keys(res));
 // Object.keys(res).forEach(key => {
  //  console.log(" key is " + key)
    // console.log("res is %o",res[key])
    Object.keys(res[0]).forEach(key2 => {
      console.log(" key2 is " + res[key2])
      tStr += res[key2]
      tStr += "\r\n"
      // console.log("res is %o",res[key])
      })
      
  //  })
   console.log("tStr is " + tStr) 

  let blob = new Blob([tStr], { type: 'text/plain;charset=utf-8' })
  saveAs(blob, 'PlanData.csv')
  
   }

}
