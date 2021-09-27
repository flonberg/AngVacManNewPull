import { THIS_EXPR } from '@angular/compiler/src/output/output_ast';
import { Component, Input, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-edit-ta',
  templateUrl: './edit-ta.component.html',
  styleUrls: ['./edit-ta.component.css']
})
export class EditTAComponent implements OnInit {
  showEdit: boolean;
  startDate: String;
  endDate: String;
  userid: String;
  theCaption: String;
  readOnly: boolean;



  constructor(private activatedRoute: ActivatedRoute) { 
    this .startDate ='09-01-2021'
  }

  ngOnInit(): void {
    this.activatedRoute.queryParams.subscribe(params => {
      this. userid = params['userid'];
    });
    this. showEdit =false;
    this .startDate =''
    this. theCaption = '';
  
  }
 prop1: any
  @Input()
  set setProp(vac){
    this. showEdit = true;
    if (vac){
    console.log("9999  vacUserid %o",  vac.userid)
    console.log("3 33333 %o", this .eUsers[vac.userid].UserID)
    }
  
    if (this.userid && this. userid == vac.userid){

      this. theCaption = "Edit Time Away"
      this. readOnly = false;
    }
    else {
      this. theCaption = ""; 
      this. readOnly = true;
    }
    
    if (vac){
      this .startDate = this. dateReformat(vac.startDate);
      this .endDate = this.dateReformat(vac.endDate);
      console.log("vvvv %o", vac)
    }
  }
  eUsers: any;
    @Input()
    set editUsers(us){
      this .eUsers = us;
    }

  dateReformat(dt){
    var today = new Date(dt);
    console.log("dt sis %o", dt)
    var dd = String(today.getUTCDate());
    var mm = String(today.getUTCMonth() + 1); //January is 0!
    var yyyy = today.getUTCFullYear();
    var todayStr = mm + '/' + dd + '/' + yyyy;

    return todayStr;
  }

}
