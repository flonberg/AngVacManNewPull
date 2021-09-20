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
  constructor(private http: HttpClient) { 
    
  }

  ngOnInit(): void {
    this.getVacs().subscribe(res =>{
      this.setData(res);
    })
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
}
