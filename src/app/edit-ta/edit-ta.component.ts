import { Component, Input, OnInit } from '@angular/core';

@Component({
  selector: 'app-edit-ta',
  templateUrl: './edit-ta.component.html',
  styleUrls: ['./edit-ta.component.css']
})
export class EditTAComponent implements OnInit {
  showEdit: boolean;

  constructor() { }

  ngOnInit(): void {
    this. showEdit =false;
  }
 prop1: any
  @Input()
  set setProp(vac){
    this. showEdit = true;
    console.log("vvvv %o", vac)
  }

}
