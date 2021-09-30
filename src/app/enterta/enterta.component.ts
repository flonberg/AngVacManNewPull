import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

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
  constructor( public datePipe: DatePipe, private activatedRoute: ActivatedRoute ) { 
    
  }
  ngOnInit(): void {
    this. activatedRoute.queryParams.subscribe(params =>{
      this .userid = params['userid']
      console.log("enterta userid %o", this .userid)
    })
  }
  dateRangeChange(dateRangeStart: HTMLInputElement, dateRangeEnd: HTMLInputElement) {
    var tDate = new Date(dateRangeStart.value)
     this .dateRangeStart = this.datePipe.transform(tDate, 'yyyy-MM-dd')
    if (  dateRangeEnd.value  ){
     var eDate = new Date(dateRangeEnd.value)
    this .dateRangeEnd = this.datePipe.transform(eDate, 'yyyy-MM-dd')
  }
  console.log("change")
 }

}
