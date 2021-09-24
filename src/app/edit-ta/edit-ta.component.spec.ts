import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { EditTAComponent } from './edit-ta.component';

describe('EditTAComponent', () => {
  let component: EditTAComponent;
  let fixture: ComponentFixture<EditTAComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ EditTAComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(EditTAComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
