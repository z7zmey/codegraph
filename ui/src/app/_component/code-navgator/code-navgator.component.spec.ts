import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CodeNavgatorComponent } from './code-navgator.component';

describe('CodeNavgatorComponent', () => {
  let component: CodeNavgatorComponent;
  let fixture: ComponentFixture<CodeNavgatorComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ CodeNavgatorComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CodeNavgatorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should be created', () => {
    expect(component).toBeTruthy();
  });
});
