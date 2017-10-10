import { Injectable } from '@angular/core';
import { Http, Headers, RequestOptions, Response } from '@angular/http';
import { FormGroup } from '@angular/forms';
import { Observable } from 'rxjs/Observable';
import 'rxjs/add/operator/map';

@Injectable()
export class ApiService {

  constructor(private http: Http) {
  }

  public searchMethod(name: string): Observable<Response> {
    const url = '/api/methods?name=' + name;
    return this.http.get(url);
  }

  public getMethodCalls(name: string): Observable<Response> {
    const url = '/api/method/calls?name=' + name;
    return this.http.get(url);
  }

  public getMethodCode(name): Observable<Response> {
    const url = '/api/method/code?name=' + name;
    return this.http.get(url);
  }

  public getUml(path): Observable<Response> {
    const url = '/api/uml?path=' + path;
    return this.http.get(url);
  }

  public getPathTree(): Observable<Response> {
    const url = '/api/tree';
    return this.http.get(url);
  }
}
