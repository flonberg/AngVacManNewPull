import { Component } from '@angular/core';
import { HttpClient } from '@angular/common/http';
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
   this.planData = res;
   console.log("this pllanData is %o", this.planData)
 }

}
