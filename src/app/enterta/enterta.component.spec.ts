import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { EntertaComponent } from './enterta.component';

describe('EntertaComponent', () => {
  let component: EntertaComponent;
  let fixture: ComponentFixture<EntertaComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ EntertaComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(EntertaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
