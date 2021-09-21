import { HttpClient } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-plans',
  templateUrl: './plans.component.html',
  styleUrls: ['./plans.component.css']
})
export class PlansComponent implements OnInit {
  panelOpenState: boolean;
  vacData: any;
  calDates: Date[];
  constructor(private http: HttpClient) { 
    
  }

  ngOnInit(): void {
    this.getVacs().subscribe(res =>{
      this.setData(res);
    })
    this. setCalDates();
  }
  getVacs(){
    var url = 'https://whiteboard.partners.org/esb/FLwbe/vacation/getVacs.php';
    return this .http.get(url)
    }
    setData(res ) {
      console.log("111")
      this.vacData = res;
      console.log(this.vacData)
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
}
