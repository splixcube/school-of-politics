import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PaywallPage } from './paywall.page';

describe('PaywallPage', () => {
  let component: PaywallPage;
  let fixture: ComponentFixture<PaywallPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(PaywallPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
