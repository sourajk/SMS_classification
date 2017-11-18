
class Values
{
    public double values[];
    public Values(String s1, String s2, String s3, String s4)
    {
        double value[] = {Double.parseDouble(s1),Double.parseDouble(s2),Double.parseDouble(s3),Double.parseDouble(s4)};
        values = value;
    }
    public String print()
    {
        String s="";
        for ( double d : values) 
            s+=" "+ d;
        
        return s;
    }
}