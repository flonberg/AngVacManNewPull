import { Component, Input, OnInit } from '@angular/core';

@Component({
  selector: 'app-edit-ta',
  templateUrl: './edit-ta.component.html',
  styleUrls: ['./edit-ta.component.css']
})
export class EditTAComponent implements OnInit {
  showEdit: boolean;
  startDate: String;

  constructor() { 
    this .startDate ='09-01-2021'
  }

  ngOnInit(): void {
    this. showEdit =false;
    this .startDate ='09-01-2021'
  }
 prop1: any
  @Input()
  set setProp(vac){
    this. showEdit = true;
    console.log("vvvv %o", vac)
  }

}
