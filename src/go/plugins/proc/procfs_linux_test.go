// +build linux

/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

package proc

import "testing"

var testData = []byte(`
Name:	foo-bar
VmPeak:	    6032 kB
b:
VmHWM:	     456 mB
VmRSS:	     456 GB
VmData:	     376 TB
fail:		 abs TB
`)

func Test_byteFromProcFileData(t *testing.T) {
	type args struct {
		data      []byte
		valueName string
	}
	tests := []struct {
		name      string
		args      args
		wantValue float64
		wantFound bool
		wantErr   bool
	}{
		{"+kB", args{testData, "VmPeak"}, 6032 * 1024, true, false},
		{"+mB", args{testData, "VmHWM"}, 456 * 1024 * 1024, true, false},
		{"+GB", args{testData, "VmRSS"}, 456 * 1024 * 1024 * 1024, true, false},
		{"+TB", args{testData, "VmData"}, 376 * 1024 * 1024 * 1024 * 1024, true, false},
		{"+TB", args{testData, "VmData"}, 376 * 1024 * 1024 * 1024 * 1024, true, false},
		{"-malformed_line", args{testData, "b"}, 0, false, false},
		{"-incorrect_value", args{testData, "fail"}, 0, true, true},
	}
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			gotValue, gotFound, err := byteFromProcFileData(tt.args.data, tt.args.valueName)
			if (err != nil) != tt.wantErr {
				t.Errorf("byteFromProcFileData() error = %v, wantErr %v", err, tt.wantErr)
				return
			}
			if gotValue != tt.wantValue {
				t.Errorf("byteFromProcFileData() gotValue = %v, want %v", gotValue, tt.wantValue)
			}
			if gotFound != tt.wantFound {
				t.Errorf("byteFromProcFileData() gotFound = %v, want %v", gotFound, tt.wantFound)
			}
		})
	}
}
